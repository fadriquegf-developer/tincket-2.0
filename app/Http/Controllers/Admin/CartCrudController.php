<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cart;
use App\Models\Brand;
use setasign\Fpdi\Fpdi;
use App\Scopes\BrandScope;
use App\Models\Application;
use App\Models\Inscription;
use App\Models\PartialRefund;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Prologue\Alerts\Facades\Alert;
use App\Traits\CrudPermissionTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Services\Payment\PaymentServiceFactory;
use App\Services\Payment\RedsysRefundService;
use App\Services\Payment\PartialRefundService;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;


/**
 * Class CartCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class CartCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\Pro\Http\Controllers\Operations\CustomViewOperation;
    use CrudPermissionTrait;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(Cart::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/cart');
        CRUD::setEntityNameStrings(__('menu.cart'), __('menu.carts'));
        $this->setAccessUsingPermissions();
    }

    protected function setupShowOperation(): void
    {
        $this->crud->setShowView('core.cart.show');
    }

    protected function setupListOperation()
    {
        CRUD::enableExportButtons();
        CRUD::setOperationSetting('lineButtonsAsDropdown', true);

        $brand = get_current_brand();

        CRUD::addColumn([
            'name' => 'confirmation_code',
            'label' => __('backend.cart.confirmationcode'),
            'type' => 'text',
            'searchLogic' => fn($query, $column, $searchTerm) =>
            $query->where(function ($q) use ($column, $searchTerm) {
                $q->where($column['name'], 'like', "%{$searchTerm}%")
                    ->orWhere('comment', 'like', "%{$searchTerm}%");
            }),
        ]);

        CRUD::addColumn([
            'name' => 'client',
            'type' => 'relationship',
            'label' => __('backend.cart.client'),
            'attribute' => 'full_name_email',
            'limit' => true,
            'searchLogic' => function ($query, $column, $searchTerm) {
                $query->orWhereHas('client', function ($q) use ($searchTerm) {
                    $q->where('clients.email', 'like', "%$searchTerm%")
                        ->orWhere('clients.surname', 'like', "%$searchTerm%")
                        ->orWhere('clients.name', 'like', "%$searchTerm%");
                });
            },
        ]);

        if (get_brand_capability() != 'promoter') {
            CRUD::addColumn([
                'name' => 'promoter_brand',
                'label' => 'Promotor',
                'type' => 'closure',
                'function' => function ($entry) use ($brand) {
                    // Cargar inscripciones con session y su brand
                    if (!$entry->relationLoaded('allInscriptions')) {
                        $entry->load(['allInscriptions.session.brand']);
                    }

                    // Obtener la primera brand promotora (de la session) diferente a la actual
                    $promoterBrand = $entry->allInscriptions
                        ->pluck('session.brand')
                        ->filter()
                        ->unique('id')
                        ->first(fn($b) => $b && $b->id !== $brand->id);

                    return $promoterBrand ? $promoterBrand->name : '-';
                },
                'orderable' => false,
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhereHas('allInscriptions.session.brand', function ($q) use ($searchTerm) {
                        $q->where('name', 'like', '%' . $searchTerm . '%')
                            ->orWhere('name', 'like', '%' . $searchTerm . '%');
                    });
                },
            ]);
        }

        CRUD::addColumn([
            'name' => 'price_sold',
            'type' => 'number',
            'label' => __('backend.cart.amount'),
            'suffix' => ' €',
            'decimals' => 2,
            'dec_point' => ',',
            'thousands_sep' => '.',
            'limit' => true,
            'orderable' => false,
        ]);

        CRUD::addColumn([
            'name' => 'comment',
            'label' => __('backend.cart.comment'),
            'type' => 'text'
        ]);

        CRUD::addColumn([
            'name' => 'created_at',
            'label' => __('backend.cart.created'),
            'type' => 'date.str'
        ]);

        CRUD::addColumn([
            'name' => 'updated_at',
            'label' => __('backend.cart.modified'),
            'type' => 'date.str'
        ]);


        // Filtros

        CRUD::addFilter(
            [
                'name' => 'last_3_months',
                'type' => 'simple',
                'label' => __('backend.cart.last_3_months'),
            ],
            false,
            fn() =>
            $this->crud->addClause(
                'whereDate',
                'carts.created_at',
                '>=',
                Carbon::now()->subMonthsNoOverflow(3)->startOfDay()
            )
        );

        CRUD::addFilter([
            'name' => 'confirmed',
            'type' => 'simple',
            'label' => __('backend.cart.confirmed')
        ], false, function () {
            CRUD::addClause('confirmed');
        });

        CRUD::addFilter(
            [
                'type' => 'date_range',
                'name' => 'from_to',
                'label' => __('backend.cart.createdbetween'),
            ],
            false,
            function (string $value) {
                $dates = json_decode($value, true);
                $from = Carbon::parse($dates['from'])->startOfDay();
                $to = Carbon::parse($dates['to'])->endOfDay();
                CRUD::addClause(
                    'whereBetween',
                    'carts.created_at',
                    [$from, $to]
                );
            }
        );

        $this->runCustomViews([
            'setupTrashCartsView' => __('backend.client.trash'),
        ]);
    }

    protected function setupTrashCartsView(): void
    {
        $this->crud->addClause('onlyTrashed');
        $this->crud->addClause('whereHas', 'inscriptions', function ($query) {
            $query->withTrashed();
        });
        $this->crud->denyAccess(['create', 'update', 'delete', 'show']);

        $this->crud->setColumns([
            [
                'name' => 'id',
                'label' => ' #ID',
                'type' => 'text',
            ],
            [
                'name' => 'confirmation_code',
                'label' => __('backend.cart.confirmationcode'),
                'type' => 'text',
            ],
            [
                'name' => 'client',
                'type' => 'relationship',
                'label' => __('backend.client.name'),
                'attribute' => 'name',
                'wrapper' => ['class' => 'form-group col-md-2'],
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhereHas('client', function ($q) use ($searchTerm) {
                        $q->where('clients.name', 'like', "%{$searchTerm}%");
                    });
                },
            ],
            [
                'name' => 'client',
                'type' => 'relationship',
                'label' => __('backend.client.surname'),
                'attribute' => 'surname',
                'wrapper' => ['class' => 'form-group col-md-2'],
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhereHas('client', function ($q) use ($searchTerm) {
                        $q->where('clients.surname', 'like', "%{$searchTerm}%");
                    });
                },
            ],
            [
                'name' => 'client',
                'type' => 'relationship',
                'label' => __('backend.client.email'),
                'attribute' => 'email',
                'wrapper' => ['class' => 'form-group col-md-2'],
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhereHas('client', function ($q) use ($searchTerm) {
                        $q->where('clients.email', 'like', "%{$searchTerm}%");
                    });
                },
            ],
            [
                'name' => 'deleted_at',
                'label' => __('backend.deleted_at'),
                'type' => 'date_str',
            ],
            [
                'name' => 'deleted_user_id',
                'label' => __('backend.deleted_by'),
                'type' => 'closure',
                'function' => function ($cart) {

                    $user = \App\Models\User::find($cart->deleted_user_id);
                    return $user ? e($user->name) : '-';
                },
                'searchLogic' => function ($query, $column, $searchTerm) {
                    $query->orWhereHas('deletedUser', function ($q) use ($searchTerm) {
                        $q->where('users.name', 'like', "%{$searchTerm}%");
                    });
                },
            ],
        ]);

        $this->crud->addButtonFromView('line', 'restore', 'restore_cart', 'beginning');
        $this->crud->addButtonFromView('line', 'show_trashed', 'show_trashed_cart', 'beginning');
    }

    public function showTrashed($id)
    {
        $cart = Cart::withTrashed()
            ->with([
                'inscriptions' => function ($query) {
                    $query->withTrashed();
                },
                'inscriptions.session.event',
                'inscriptions.rate',
                'inscriptions.slot',
                'groupPacks' => function ($query) {
                    $query->withTrashed();
                },
                'groupPacks.inscriptions' => function ($query) {
                    $query->withTrashed();
                },
                'groupPacks.inscriptions.session.event',
                'groupPacks.pack',
                'client',
                'deletedUser'
            ])
            ->findOrFail($id);

        // Obtener el ÚLTIMO payment (el más reciente), incluyendo eliminados
        $payment = $cart->payments()
            ->withTrashed()
            ->whereNotNull('paid_at')
            ->latest('created_at')  // ← El más reciente
            ->first();

        $this->crud->setOperationSetting('entry', $cart);

        return view('core.cart.show-trashed', [
            'crud' => $this->crud,
            'entry' => $cart,
            'payment' => $payment,
            'title' => 'Carrito Eliminado #' . $cart->id
        ]);
    }

    public function updateComment(Request $request, $id)
    {
        $entry = Cart::findOrFail($id);
        $entry->comment = $request->input('comment');
        $entry->save();

        Alert::success('Carrito guardado correctamente.')->flash();

        return redirect()->back()->with('success', 'Carrito guardado correctamente.');
    }

    public function restore($id)
    {
        $cart = Cart::withTrashed()->findOrFail($id);
        $cart->restore();

        Alert::success('Carrito recuperado correctamente')->flash();

        return redirect(url($this->crud->route . '/view/trash-carts'));
    }


    public function download(Request $request, Cart $cart)
    {
        $pdf = new Fpdi();
        $files = [];

        // ✅ Determinar el formato UNA SOLA VEZ basándose en el seller del cart
        $cart->load('seller'); // Eager load del seller
        $isWebSale = $cart->seller instanceof Application;

        foreach ($cart->inscriptions as $inscription) {
            $pdfPath = $this->getInscriptionPdfPath($inscription);

            if (!$pdfPath || !file_exists($pdfPath)) {
                $this->regenerateInscriptionPdf($inscription, $isWebSale);
                $pdfPath = $this->getInscriptionPdfPath($inscription);
            }

            if ($pdfPath && file_exists($pdfPath)) {
                $files[] = $pdfPath;
            } else {
                \Log::error('[DOWNLOAD PDF] Fallo: no se pudo obtener el PDF para inscripción', [
                    'inscription_id' => $inscription->id,
                    'pdf_field' => $inscription->pdf,
                    'computed_path' => $pdfPath
                ]);
            }
        }

        foreach ($cart->groupPacks as $groupPack) {
            foreach ($groupPack->inscriptions as $inscription) {
                $pdfPath = $this->getInscriptionPdfPath($inscription);

                if (!$pdfPath || !file_exists($pdfPath)) {
                    $this->regenerateInscriptionPdf($inscription, $isWebSale);
                    $pdfPath = $this->getInscriptionPdfPath($inscription);
                }

                if ($pdfPath && file_exists($pdfPath)) {
                    $files[] = $pdfPath;
                } else {
                    \Log::error('[DOWNLOAD PDF] Fallo: no se pudo obtener el PDF para inscripción de pack', [
                        'inscription_id' => $inscription->id,
                        'pdf_field' => $inscription->pdf,
                        'computed_path' => $pdfPath
                    ]);
                }
            }
        }

        if (empty($files)) {
            abort(404, 'No hay PDFs disponibles para este carrito.');
        }

        foreach ($files as $file) {
            try {
                $pageCount = $pdf->setSourceFile($file);
                for ($i = 1; $i <= $pageCount; $i++) {
                    $tplIdx = $pdf->importPage($i);
                    $size = $pdf->getTemplateSize($tplIdx);
                    $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                    $pdf->useTemplate($tplIdx);
                }
            } catch (\Exception $e) {
                \Log::error('[DOWNLOAD PDF] Error al fusionar PDF', [
                    'file' => $file,
                    'exception' => $e->getMessage()
                ]);
            }
        }

        $outputDir = storage_path('app/public/carts');
        if (!file_exists($outputDir)) {
            mkdir($outputDir, 0755, true);
        }

        $outputPath = $outputDir . '/' . uniqid() . '.pdf';
        $pdf->Output($outputPath, 'F');

        return response()->download($outputPath)->deleteFileAfterSend(true);
    }

    /**
     * Normaliza la ruta del PDF de una inscripción
     */
    private function getInscriptionPdfPath($inscription)
    {
        if (!$inscription->pdf) {
            return null;
        }

        $relativePath = $inscription->pdf;

        // Si ya tiene "app/" al principio, usarlo directamente
        if (str_starts_with($relativePath, 'app/')) {
            return storage_path($relativePath);
        }

        // Si no tiene prefijo, asumir que está en storage/app/
        return storage_path('app/' . ltrim($relativePath, '/'));
    }

    /**
     * Regenera el PDF de una inscripción usando el servicio local
     * @param Inscription $inscription
     * @param bool $isWebSale Si es true usa formato web, si no formato taquilla
     * @return string|false
     */
    protected function regenerateInscriptionPdf($inscription, $isWebSale)
    {
        if ($isWebSale) {
            $pdfParams = brand_setting('base.inscription.ticket-web-params');
        } else {
            $pdfParams = brand_setting('base.inscription.ticket-office-params');
        }

        $destinationPath = 'pdf/inscriptions';
        $pdfFileName = $inscription->pdf_name;

        // Crear directorio si no existe
        $fullDirectoryPath = storage_path('app/' . $destinationPath);
        if (!is_dir($fullDirectoryPath)) {
            mkdir($fullDirectoryPath, 0775, true);
        }

        // 🔥 NUEVO: Construir URL interna
        $internalPdfUrl = url(route('open.inscription.pdf', array_merge(
            [
                'inscription' => $inscription,
                'token' => $inscription->cart->token,
                'brand_code' => $inscription->cart->brand->code_name
            ],
            $pdfParams
        )));

        try {
            // 🔥 NUEVO: Usar servicio local
            $pdfService = app(\App\Services\PdfGeneratorService::class);
            $pdf_content = $pdfService->generateFromUrl($internalPdfUrl, $pdfParams);

            if (empty($pdf_content)) {
                throw new \Exception("PDF content is empty");
            }

            // Guardar en storage/app/
            $saved = \Storage::disk()->put("$destinationPath/$pdfFileName", $pdf_content);

            if (!$saved) {
                return false;
            }

            // Actualizar ruta en BD (sin app/ al inicio)
            $inscription->pdf = "$destinationPath/$pdfFileName";
            $inscription->save();

            return \Storage::disk()->path("$destinationPath/$pdfFileName");
        } catch (\Exception $e) {
            \Log::error("TICKET of Inscription ID {$inscription->id} failed", [
                "exception" => $e->getMessage(),
                "trace" => $e->getTraceAsString()
            ]);

            if (app()->environment('production')) {
                throw $e;
            } else {
                return false;
            }
        }
    }

    public function regeneratePDF(Request $request, Cart $cart)
    {
        if (!$cart->confirmation_code) {
            abort(500, 'Cart not confirmed');
        }

        $brandId = get_current_brand()->id;

        if ($cart->brand_id !== $brandId) {
            abort(403, 'Unauthorized action.');
        }

        $send_mail = $request->input('send') === "true";

        $params = $cart->seller instanceof Application
            ? ['send_mail' => $send_mail, 'pdf' => brand_setting('base.inscription.ticket-web-params')]
            : ['send_mail' => $send_mail, 'pdf' => brand_setting('base.inscription.ticket-office-params')];

        // Si envía email, usar cola; si no, ejecutar inmediatamente
        if ($send_mail) {
            \App\Jobs\CartConfirm::dispatch($cart, $params);
            Alert::success('La regeneración de PDFs y el envío de emails se están procesando. Recibirás el email en unos minutos.')->flash();
        } else {
            \App\Jobs\CartConfirm::dispatchSync($cart, $params);
            Alert::success('PDFs regenerados correctamente.')->flash();
        }

        return redirect()->route('cart.show', $cart);
    }


    public function changeGateway(Request $request, Cart $cart)
    {
        $payment = $cart->payment;
        $gateway = $request->input('gateway');

        // Si es cash o card, el gateway real es TicketOffice
        if (in_array($gateway, ['cash', 'card'])) {
            $payment->gateway = 'TicketOffice';

            $resp = json_decode($payment->gateway_response ?? '{}', true);
            $resp['payment_type'] = $gateway;  // 'cash' o 'card'
            $payment->gateway_response = json_encode($resp, JSON_UNESCAPED_UNICODE);
        } else {
            // Redsys Redirect u otro gateway online
            $payment->gateway = $gateway;

            // Limpiamos payment_type del JSON si existía
            $resp = json_decode($payment->gateway_response ?? '{}', true);
            unset($resp['payment_type']);
            $payment->gateway_response = json_encode($resp, JSON_UNESCAPED_UNICODE);
        }

        $payment->save();

        Alert::success('Plataforma de pago actualizada correctamente')->flash();

        return redirect()->route('cart.show', $cart->id);
    }

    public function paymentOffice(Request $request)
    {
        $cart = Cart::find($request->cart_id);

        $payment_service = PaymentServiceFactory::create('TicketOffice');
        $payment_service->setPaymentType($request->payment_type);
        $payment_service->purchase($cart);
        $payment_service->confirmPayment();

        Alert::success(trans('backpack::crud.paid_cart_office'))->flash();

        return \Redirect::to(route('cart.show', [$cart]));
    }

    public function changeClient(Request $request, $id)
    {
        $cart = Cart::findOrFail($id);
        $cart->client_id = $request->input('client_id');
        $cart->save();

        Alert::success('Cliente cambiado correctamente')->flash();
        return redirect()->back();
    }

    /**
     * Marcar reembolso como completado (manual)
     * 
     * Cuando el reembolso se hace manualmente desde el panel de Redsys,
     * usar este método para registrarlo y eliminar el carrito.
     */
    public function markRefunded(Request $request, $id)
    {
        $cart = Cart::withoutGlobalScope(BrandScope::class)->findOrFail($id);

        // Verificar permisos
        if (!$this->canManageRefunds()) {
            Alert::error(__('refund.no_permission'))->flash();
            return redirect()->back();
        }

        // Verificar que el pago existe
        $payment = $cart->payment;
        if (!$payment) {
            Alert::error(__('refund.not_paid'))->flash();
            return redirect()->back();
        }

        // Verificar que no está ya reembolsado
        if ($payment->isRefunded()) {
            Alert::warning(__('refund.already_refunded'))->flash();
            return redirect()->back();
        }

        // Validar
        $validated = $request->validate([
            'refund_reference' => 'required|string|max:100',
            'refund_notes' => 'nullable|string|max:500',
        ]);

        // Si no estaba marcado para reembolso, marcarlo ahora
        if (!$payment->requires_refund) {
            $payment->requires_refund = true;
            $payment->refund_reason = 'admin_manual';
        }

        // Marcar como reembolsado
        $payment->markAsRefunded($validated['refund_reference']);

        // Añadir comentario al carrito ANTES de eliminarlo
        $refundComment = "\n\n[REEMBOLSO MANUAL " . now()->format('d/m/Y H:i') . "]\nRef: {$validated['refund_reference']}";
        if (!empty($validated['refund_notes'])) {
            $refundComment .= "\nNotas: " . $validated['refund_notes'];
        }
        $refundComment .= "\nProcesado por: " . auth()->user()->email;

        $cart->comment = trim($cart->comment . $refundComment);
        $cart->save();

        // Log antes de eliminar
        \Log::info('Payment marked as refunded, deleting cart to free slots', [
            'cart_id' => $cart->id,
            'payment_id' => $payment->id,
            'refund_reference' => $validated['refund_reference'],
            'amount' => $cart->priceSold,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()->email,
        ]);

        // ═══════════════════════════════════════════════════════════════
        // ELIMINAR CARRITO PARA LIBERAR BUTACAS
        // El CartObserver se encarga de:
        // - Liberar slots en Redis
        // - Soft delete de inscripciones
        // - Limpiar SessionTempSlots
        // ═══════════════════════════════════════════════════════════════
        $cart->delete();

        Alert::success(__('refund.mark_success') ?? 'Reembolso registrado correctamente. Carrito eliminado y butacas liberadas.')->flash();

        // Redirigir a la lista de carritos eliminados
        return redirect()->to(url($this->crud->route . '/view/trash-carts'));
    }

    /**
     * Solicitar reembolso para un carrito (marcar como pendiente)
     * 
     * Este método marca el pago para reembolso cuando un cliente llama
     * solicitando una devolución. Después, se puede procesar manualmente
     * o automáticamente con Redsys.
     */
    public function requestRefund(Request $request, $id)
    {
        $cart = Cart::withoutGlobalScope(BrandScope::class)->findOrFail($id);

        // Verificar permisos
        if (!$this->canManageRefunds()) {
            Alert::error(__('refund.no_permission'))->flash();
            return redirect()->back();
        }

        // Verificar que tiene pago confirmado
        $payment = $cart->payment;
        if (!$payment || !$payment->paid_at) {
            Alert::error(__('refund.not_paid'))->flash();
            return redirect()->back();
        }

        // Verificar que no está ya marcado/reembolsado
        if ($payment->isRefunded()) {
            Alert::warning(__('refund.already_refunded'))->flash();
            return redirect()->back();
        }

        // Validar
        $validated = $request->validate([
            'refund_reason' => 'required|string|in:customer_request,event_cancelled,duplicate_payment,admin_manual,other',
            'refund_notes' => 'nullable|string|max:500',
        ]);

        // Marcar para reembolso
        $payment->markForRefund($validated['refund_reason'], [
            'requested_by' => auth()->user()->email,
            'requested_at' => now()->toIso8601String(),
            'notes' => $validated['refund_notes'] ?? null,
            'cart_total' => $cart->priceSold,
            'client_email' => $cart->client->email ?? null,
        ]);

        // Añadir comentario al carrito
        $reasonText = __('refund.reasons.' . $validated['refund_reason']);
        $cart->comment = trim($cart->comment . "\n\n[DEVOLUCIÓN SOLICITADA " . now()->format('d/m/Y H:i') . "]\nMotivo: {$reasonText}\n" . ($validated['refund_notes'] ?? ''));
        $cart->save();

        Alert::success(__('refund.request_success'))->flash();

        return redirect()->back();
    }

    /**
     * Procesar reembolso automático con Redsys
     * 
     * Este método intenta hacer la devolución automáticamente a través
     * de la API de Redsys. Si tiene éxito, elimina el carrito para
     * liberar las butacas.
     */
    public function processRefund(Request $request, $id)
    {
        $cart = Cart::withoutGlobalScope(BrandScope::class)->findOrFail($id);

        // Verificar permisos (solo superadmin puede procesar automáticamente)
        if (!auth()->user()->isSuperuser()) {
            Alert::error(__('refund.no_permission_auto'))->flash();
            return redirect()->back();
        }

        // Verificar que tiene pago
        $payment = $cart->payment;
        if (!$payment || !$payment->paid_at) {
            Alert::error(__('refund.not_paid'))->flash();
            return redirect()->back();
        }

        // Verificar que no está ya reembolsado
        if ($payment->isRefunded()) {
            Alert::warning(__('refund.already_refunded'))->flash();
            return redirect()->back();
        }

        // Validar importe (opcional, por defecto total)
        $validated = $request->validate([
            'refund_amount' => 'nullable|numeric|min:0.01',
        ]);

        $amountCents = null;
        if (!empty($validated['refund_amount'])) {
            $amountCents = (int) round($validated['refund_amount'] * 100);
        }

        // Obtener motivo (si no está marcado, usar admin_manual)
        $reason = $payment->refund_reason ?? 'admin_manual';

        // Procesar con Redsys
        $refundService = new RedsysRefundService();
        $result = $refundService->processRefund($payment, $amountCents, $reason);

        if ($result['success']) {
            // Añadir comentario al carrito ANTES de eliminarlo
            $cart->comment = trim($cart->comment . "\n\n[REEMBOLSO AUTOMÁTICO " . now()->format('d/m/Y H:i') . "]\nRef: {$result['refund_reference']}\nImporte: " . number_format(($result['amount_cents'] / 100), 2) . " €\nProcesado por: " . auth()->user()->email);
            $cart->save();

            // Log antes de eliminar
            \Log::info('Auto refund successful, deleting cart to free slots', [
                'cart_id' => $cart->id,
                'payment_id' => $payment->id,
                'refund_reference' => $result['refund_reference'],
                'amount_cents' => $result['amount_cents'],
                'user_id' => auth()->id(),
            ]);

            // ═══════════════════════════════════════════════════════════════
            // ELIMINAR CARRITO PARA LIBERAR BUTACAS
            // El CartObserver se encarga de:
            // - Liberar slots en Redis
            // - Soft delete de inscripciones
            // - Limpiar SessionTempSlots
            // ═══════════════════════════════════════════════════════════════
            $cart->delete();

            Alert::success(__('refund.auto_success', [
                'reference' => $result['refund_reference'],
                'amount' => number_format(($result['amount_cents'] / 100), 2),
            ]))->flash();

            // Redirigir a la lista de carritos eliminados
            return redirect()->to(url($this->crud->route . '/view/trash-carts'));
        } else {
            // Error - mostrar mensaje
            Alert::error(__('refund.auto_error', [
                'message' => $result['message'],
            ]))->flash();

            // Log del error
            \Log::error('Auto refund failed', [
                'cart_id' => $cart->id,
                'payment_id' => $payment->id,
                'error_code' => $result['error_code'] ?? null,
                'message' => $result['message'],
            ]);
        }

        return redirect()->back();
    }

    /**
     * Verificar si el usuario puede gestionar reembolsos
     */
    private function canManageRefunds(): bool
    {
        $user = auth()->user();

        if (!$user) {
            return false;
        }

        // Superadmin siempre puede
        if ($user->isSuperuser()) {
            return true;
        }

        // Verificar permiso específico si existe
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('manage refunds');
        }

        return false;
    }

    public function destroyInscription($cartId, $inscriptionId)
    {
        $this->crud->hasAccessOrFail('delete');

        $cart = \App\Models\Cart::findOrFail($cartId);

        // Buscar inscripción sin BrandScope
        $inscription = \App\Models\Inscription::withoutGlobalScope(\App\Scopes\BrandScope::class)
            ->where('cart_id', $cart->id)
            ->findOrFail($inscriptionId);

        if ($inscription->slot_id) {
            try {
                app(\App\Services\InscriptionService::class)->releaseSlot($inscription);
            } catch (\Exception $e) {
                \Log::warning('Error liberando slot', ['inscription_id' => $inscription->id, 'error' => $e->getMessage()]);
                $inscription->delete();
            }
        } else {
            $inscription->delete();
        }

        return response()->json(['success' => true]);
    }

    /**
     * Mostrar modal de devolución parcial con inscripciones disponibles
     * 
     * Este endpoint devuelve JSON con las inscripciones que se pueden devolver
     */
    public function getPartialRefundData($id)
    {
        $cart = Cart::withoutGlobalScope(BrandScope::class)
            ->with(['inscriptions' => function ($q) {
                $q->whereNull('deleted_at')
                    ->with(['session.event', 'slot', 'rate']);
            }])
            ->findOrFail($id);

        // Verificar permisos
        if (!$this->canManageRefunds()) {
            return response()->json([
                'success' => false,
                'message' => __('refund.no_permission'),
            ], 403);
        }

        // Verificar que tiene pago
        if (!$cart->payment || !$cart->payment->paid_at) {
            return response()->json([
                'success' => false,
                'message' => __('refund.not_paid'),
            ], 400);
        }

        // Obtener servicio y datos
        $service = new PartialRefundService();
        $inscriptions = $service->getRefundableInscriptions($cart);
        $refundHistory = $service->getRefundHistory($cart);
        $totalRefunded = $service->getTotalRefunded($cart);

        // Formatear inscripciones para el frontend
        $formattedInscriptions = $inscriptions->map(function ($inscription) {
            return [
                'id' => $inscription->id,
                'event_name' => $inscription->session?->event?->name ?? 'Sin evento',
                'session_name' => $inscription->session?->name ?? 'Sin sesión',
                'session_date' => $inscription->session?->starts_on?->format('d/m/Y H:i'),
                'slot_name' => $inscription->slot?->name ?? 'Sin butaca',
                'rate_name' => $inscription->rate?->name ?? 'Sin tarifa',
                'barcode' => $inscription->barcode,
                'price_sold' => (float) $inscription->price_sold,
                'price_sold_formatted' => number_format($inscription->price_sold, 2) . ' €',
            ];
        });

        // Formatear historial
        $formattedHistory = $refundHistory->map(function ($refund) {
            return [
                'id' => $refund->id,
                'amount' => (float) $refund->amount,
                'amount_formatted' => number_format($refund->amount, 2) . ' €',
                'status' => $refund->status,
                'status_badge' => $refund->status_badge,
                'reason' => $refund->reason_text,
                'refund_reference' => $refund->refund_reference,
                'created_at' => $refund->created_at->format('d/m/Y H:i'),
                'refunded_at' => $refund->refunded_at?->format('d/m/Y H:i'),
                'inscription_count' => $refund->items->count(),
                'items' => $refund->items->map(function ($item) {
                    return [
                        'slot_name' => $item->slot_name ?? 'Sin butaca',
                        'rate_name' => $item->rate_name,
                        'price_sold' => number_format($item->price_sold, 2) . ' €',
                    ];
                }),
            ];
        });

        return response()->json([
            'success' => true,
            'cart_id' => $cart->id,
            'confirmation_code' => $cart->confirmation_code,
            'original_amount' => (float) $cart->priceSold,
            'original_amount_formatted' => number_format($cart->priceSold, 2) . ' €',
            'total_refunded' => $totalRefunded,
            'total_refunded_formatted' => number_format($totalRefunded, 2) . ' €',
            'remaining_amount' => (float) ($cart->priceSold - $totalRefunded),
            'remaining_amount_formatted' => number_format($cart->priceSold - $totalRefunded, 2) . ' €',
            'inscriptions' => $formattedInscriptions,
            'refund_history' => $formattedHistory,
            'can_auto_refund' => $this->canAutoRefund($cart),
        ]);
    }

    /**
     * Crear solicitud de devolución parcial
     */
    public function requestPartialRefund(Request $request, $id)
    {
        $cart = Cart::withoutGlobalScope(BrandScope::class)->findOrFail($id);

        // Verificar permisos
        if (!$this->canManageRefunds()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('refund.no_permission'),
                ], 403);
            }
            Alert::error(__('refund.no_permission'))->flash();
            return redirect()->back();
        }

        // Validar
        $validated = $request->validate([
            'inscription_ids' => 'required|array|min:1',
            'inscription_ids.*' => 'required|integer|exists:inscriptions,id',
            'refund_reason' => 'required|string|in:customer_request,event_cancelled,duplicate_payment,admin_manual,other',
            'refund_notes' => 'nullable|string|max:500',
        ]);

        // Procesar
        $service = new PartialRefundService();
        $result = $service->createPartialRefund(
            $cart,
            $validated['inscription_ids'],
            $validated['refund_reason'],
            $validated['refund_notes'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json($result, $result['success'] ? 200 : 400);
        }

        if ($result['success']) {
            Alert::success($result['message'])->flash();
        } else {
            Alert::error($result['message'])->flash();
        }

        return redirect()->back();
    }

    /**
     * Procesar devolución parcial con Redsys
     */
    public function processPartialRefund(Request $request, $partialRefundId)
    {
        $partialRefund = PartialRefund::withoutGlobalScope(BrandScope::class)
            ->findOrFail($partialRefundId);

        // Verificar permisos (solo superadmin puede procesar automáticamente)
        if (!auth()->user()->isSuperuser()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('refund.no_permission_auto'),
                ], 403);
            }
            Alert::error(__('refund.no_permission_auto'))->flash();
            return redirect()->back();
        }

        $service = new PartialRefundService();
        $result = $service->processWithRedsys($partialRefund);

        if ($request->wantsJson()) {
            return response()->json($result, $result['success'] ? 200 : 400);
        }

        if ($result['success']) {
            Alert::success($result['message'])->flash();
        } else {
            Alert::error($result['message'])->flash();
        }

        return redirect()->back();
    }

    /**
     * Marcar devolución parcial como completada manualmente
     */
    public function markPartialRefundCompleted(Request $request, $partialRefundId)
    {
        $partialRefund = PartialRefund::withoutGlobalScope(BrandScope::class)
            ->findOrFail($partialRefundId);

        // Verificar permisos
        if (!$this->canManageRefunds()) {
            if ($request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => __('refund.no_permission'),
                ], 403);
            }
            Alert::error(__('refund.no_permission'))->flash();
            return redirect()->back();
        }

        // Validar
        $validated = $request->validate([
            'refund_reference' => 'required|string|max:100',
            'refund_notes' => 'nullable|string|max:500',
        ]);

        $service = new PartialRefundService();
        $result = $service->markAsCompletedManually(
            $partialRefund,
            $validated['refund_reference'],
            $validated['refund_notes'] ?? null
        );

        if ($request->wantsJson()) {
            return response()->json($result, $result['success'] ? 200 : 400);
        }

        if ($result['success']) {
            Alert::success($result['message'])->flash();
        } else {
            Alert::error($result['message'])->flash();
        }

        return redirect()->back();
    }

    /**
     * Verificar si se puede hacer refund automático para este carrito
     */
    private function canAutoRefund(Cart $cart): bool
    {
        // Solo Redsys soporta devoluciones automáticas
        if (!$cart->payment) {
            return false;
        }

        $supportedGateways = ['redsys', 'Redsys'];
        return in_array($cart->payment->gateway, $supportedGateways)
            && auth()->user()?->isSuperuser();
    }

    /**
     * Enviar email de pago al cliente
     * 
     * Este método se usa cuando:
     * 1. El TPV tuvo un error y necesitamos que el cliente vuelva a pagar
     * 2. Se cambió el precio del carrito y necesitamos que pague la diferencia
     * 3. El carrito nunca se pagó y queremos enviar el enlace
     * 
     * IMPORTANTE: El payment anterior se elimina con soft-delete para mantener
     * el historial de pagos anteriores.
     * 
     * @param Cart $cart
     * @return \Illuminate\Http\RedirectResponse
     */
    public function sendPaymentEmail(Cart $cart)
    {
        try {
            // ─────────────────────────────────────────────────────────────────
            // CARGAR RELACIONES NECESARIAS (sin BrandScope para promotores)
            // ─────────────────────────────────────────────────────────────────
            $cart->load([
                'client',
                'brand',
                'allInscriptions' => function ($q) {
                    $q->withoutGlobalScope(BrandScope::class);
                },
                'gift_cards',
            ]);

            // ─────────────────────────────────────────────────────────────────
            // VALIDACIONES
            // ─────────────────────────────────────────────────────────────────

            if (!$cart->client_id || !$cart->client) {
                Alert::error('El carrito no té un client assignat.')->flash();
                return redirect()->route('cart.show', $cart);
            }

            if (empty($cart->client->email)) {
                Alert::error('El client no té email configurat.')->flash();
                return redirect()->route('cart.show', $cart);
            }

            if (!$cart->allInscriptions->count() && !$cart->gift_cards->count()) {
                Alert::error('El carrito està buit.')->flash();
                return redirect()->route('cart.show', $cart);
            }

            // ─────────────────────────────────────────────────────────────────
            // OBTENER BRAND CORRECTA (puede ser de un promotor hijo)
            // ─────────────────────────────────────────────────────────────────
            $brand = $cart->brand;

            // Si el carrito pertenece a un brand padre pero las inscripciones son de un hijo
            if (!$brand && $cart->allInscriptions->isNotEmpty()) {
                $firstInscription = $cart->allInscriptions->first();
                $firstInscription->load(['session.event.brand']);
                $brand = $firstInscription->session?->event?->brand;
            }

            if (!$brand) {
                Alert::error('No s\'ha pogut determinar la brand del carrito.')->flash();
                return redirect()->route('cart.show', $cart);
            }

            // ─────────────────────────────────────────────────────────────────
            // GUARDAR ESTADO ANTERIOR EN COMENTARIOS (para auditoría)
            // ─────────────────────────────────────────────────────────────────
            $auditComment = $this->buildPaymentEmailAuditComment($cart);
            $oldConfirmationCode = $cart->confirmation_code;

            // ─────────────────────────────────────────────────────────────────
            // SOFT DELETE DEL PAYMENT ANTERIOR (si existe)
            // ─────────────────────────────────────────────────────────────────
            $payment = $cart->payment;

            if ($payment) {
                \Log::info('sendPaymentEmail: Soft-delete del payment anterior', [
                    'cart_id' => $cart->id,
                    'payment_id' => $payment->id,
                    'order_code' => $payment->order_code,
                    'paid_at' => $payment->paid_at,
                    'gateway' => $payment->gateway,
                ]);

                // Soft delete - el payment queda en BD con deleted_at
                $payment->delete();
            }

            // ─────────────────────────────────────────────────────────────────
            // ACTUALIZAR CARRITO PARA NUEVO PAGO
            // ─────────────────────────────────────────────────────────────────

            // Cambiar confirmation_code a XXXXXXXXX-{id} si no lo está ya
            if (!str_starts_with($cart->confirmation_code ?? '', 'XXXXXXXXX')) {
                $cart->confirmation_code = 'XXXXXXXXX-' . $cart->id;
            }

            // Extender expires_on para dar tiempo al cliente (60 días)
            $cart->expires_on = now()->addDays(60);

            // Añadir comentario de auditoría
            $cart->comment = trim(($cart->comment ?? '') . $auditComment);

            $cart->save();

            // ─────────────────────────────────────────────────────────────────
            // CARGAR CONTEXTO DE LA BRAND Y ENVIAR EMAIL
            // ─────────────────────────────────────────────────────────────────

            // Cargar configuración de la brand para que brand_setting() funcione
            app(\App\Http\Middleware\CheckBrandHost::class)
                ->loadBrandConfig($brand->code_name);

            $mailer = app(\App\Services\MailerService::class)->getMailerForBrand($brand);
            $mailer->to(trim($cart->client->email))
                ->send(new \App\Mail\PaymentCartMail($cart));

            \Log::info('sendPaymentEmail: Email enviat correctament', [
                'cart_id' => $cart->id,
                'client_email' => $cart->client->email,
                'brand_id' => $brand->id,
                'brand_code' => $brand->code_name,
                'old_confirmation_code' => $oldConfirmationCode,
                'new_confirmation_code' => $cart->confirmation_code,
                'user_id' => auth()->id(),
                'user_email' => auth()->user()?->email,
            ]);

            Alert::success(trans('backpack::crud.sended_payment_email'))->flash();
        } catch (\Exception $e) {
            \Log::error('sendPaymentEmail: Error al enviar email', [
                'cart_id' => $cart->id,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            Alert::error('Error al enviar l\'email: ' . $e->getMessage())->flash();
        }

        return redirect()->route('cart.show', $cart);
    }

    /**
     * Construir comentario de auditoría para el envío de email de pago
     */
    private function buildPaymentEmailAuditComment(Cart $cart): string
    {
        $comment = "\n\n[EMAIL PAGAMENT ENVIAT " . now()->format('d/m/Y H:i') . "]";

        // Guardar código de confirmación anterior si existe y no es XXXXXXXXX
        if ($cart->confirmation_code && !str_starts_with($cart->confirmation_code, 'XXXXXXXXX')) {
            $comment .= "\nCodi confirmació anterior: {$cart->confirmation_code}";
        }

        // Registrar el payment que se elimina
        if ($cart->payment) {
            $comment .= "\nPayment anterior (soft-deleted):";
            $comment .= "\n  - Order code: {$cart->payment->order_code}";
            $comment .= "\n  - Gateway: {$cart->payment->gateway}";
            if ($cart->payment->paid_at) {
                $comment .= "\n  - Pagat el: " . $cart->payment->paid_at->format('d/m/Y H:i');
            }
        }

        $comment .= "\nEnviat per: " . (auth()->user()->email ?? 'sistema');

        return $comment;
    }
}
