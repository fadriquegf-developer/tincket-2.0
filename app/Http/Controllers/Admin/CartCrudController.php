<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cart;
use App\Models\Brand;
use setasign\Fpdi\Fpdi;
use App\Scopes\BrandScope;
use App\Models\Application;
use App\Models\Inscription;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Prologue\Alerts\Facades\Alert;
use App\Traits\CrudPermissionTrait;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use App\Services\Payment\PaymentServiceFactory;
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

        if ($cart->seller instanceof Application) {
            \App\Jobs\CartConfirm::dispatchSync($cart, [
                'send_mail' => $send_mail,
                'pdf' => brand_setting('base.inscription.ticket-web-params')
            ]);
        } else {
            \App\Jobs\CartConfirm::dispatchSync($cart, [
                'send_mail' => $send_mail,
                'pdf' => brand_setting('base.inscription.ticket-office-params')
            ]);
        }

        // Mensaje diferente ya que es asíncrono
        if ($send_mail) {
            Alert::success('La regeneración de PDFs y el envío de emails se están procesando. Recibirás el email en unos minutos.')->flash();
        } else {
            Alert::success('La regeneración de PDFs se está procesando. Actualiza la página en unos minutos para verlos.')->flash();
        }

        return redirect()->route('cart.show', $cart);
    }


    public function changeGateway(Request $request, Cart $cart)
    {
        $payment = $cart->payment;

        /* 2) actualiza la columna visible --------------------------- */
        $payment->gateway = $request->input('gateway');   // <- ESTA línea faltaba

        /* 3) mantiene coherencia en el JSON (por si lo usas luego) -- */
        $resp = json_decode($payment->gateway_response ?? '{}', true);
        $resp['payment_type'] = $payment->gateway;
        $payment->gateway_response = json_encode($resp, JSON_UNESCAPED_UNICODE);
        $payment->save();

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
}
