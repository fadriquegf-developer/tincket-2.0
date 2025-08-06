<?php

namespace App\Http\Controllers\Admin;

use App\Models\Cart;
use App\Traits\CrudPermissionTrait;
use setasign\Fpdi\Fpdi;
use App\Models\Application;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Prologue\Alerts\Facades\Alert;
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
        CRUD::setEntityNameStrings(__('backend.menu.cart'), __('backend.menu.carts'));
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
                'label' => __('backend.general.deleted_at'),
                'type' => 'date_str',
            ],
            [
                'name' => 'deleted_user_id',
                'label' => __('backend.general.deleted_by'),
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

        return redirect(url($this->crud->route . '/view/trash-carts'));
    }


    public function download(Cart $cart)
    {
        $pdf = new Fpdi();
        $files = [];

        foreach ($cart->inscriptions as $inscription) {

            $pdfPath = storage_path($inscription->pdf);

            if (!file_exists($pdfPath)) {
                $this->regenerateInscriptionPdf($inscription);
                $pdfPath = storage_path($inscription->pdf); // reconstruir después de regenerar
            }

            if (file_exists($pdfPath)) {
                $files[] = $pdfPath;
            } else {
                \Log::error('[DOWNLOAD PDF] Fallo: no se pudo obtener el PDF para inscripción', [
                    'inscription_id' => $inscription->id
                ]);
            }
        }

        foreach ($cart->groupPacks as $groupPack) {

            foreach ($groupPack->inscriptions as $inscription) {

                $pdfPath = storage_path($inscription->pdf);

                if (!file_exists($pdfPath)) {
                    $this->regenerateInscriptionPdf($inscription);
                    $pdfPath = storage_path($inscription->pdf);
                }

                if (file_exists($pdfPath)) {
                    $files[] = $pdfPath;
                } else {
                    \Log::error('[DOWNLOAD PDF] Fallo: no se pudo obtener el PDF para inscripción de pack', [
                        'inscription_id' => $inscription->id
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

        $outputPath = storage_path('app/public/carts/' . uniqid() . '.pdf');

        $pdf->Output($outputPath, 'F');

        return response()->download($outputPath)->deleteFileAfterSend(true);
    }

    /**
    * Regenera el PDF de una inscripción usando el microservicio PDF externo
    * @return string|false

     */
    protected function regenerateInscriptionPdf($inscription)
    {
        if ($inscription->cart->seller instanceof Application) {
            $pdfParams = brand_setting('base.inscription.ticket-web-params');
        } else {
            $pdfParams = brand_setting('base.inscription.ticket-office-params');
        }

        $destinationPath = brand_setting('base.inscription.pdf_folder');
        $pdfFileName = $inscription->pdf_name;

        // Construir la URL interna sin url() para no doble encode
        $internalPdfUrl = route('open.inscription.pdf', array_merge(
            [
                'inscription' => $inscription,
                'token' => $inscription->cart->token
            ],
            $pdfParams
        ));

        // Construir URL completa del microservicio
        $pdfRenderer = env('TK_PDF_RENDERER', 'https://pdf.yesweticket.com');
        $pdfServiceUrl = $pdfRenderer . "/render?url=" . $internalPdfUrl;

        try {
            $response = Http::timeout(30)->get($pdfServiceUrl);

            if (!$response->successful()) {
                throw new \Exception('PDF Service failed: ' . $response->status());
            }

            // Guardar PDF en disco 'public'
            $saved = Storage::disk('public')->put("$destinationPath/$pdfFileName", $response->body());

            if (!$saved) {
                return false;
            }

            // Actualizar modelo con ruta relativa
            $inscription->pdf = "app/public/$destinationPath/$pdfFileName";
            $inscription->save();

            // Devolver ruta absoluta física del archivo
            return Storage::disk('public')->path("$destinationPath/$pdfFileName");

        } catch (\Exception $e) {
            \Log::error("TICKET of Inscription ID {$inscription->id} failed", ["exception" => $e]);
            if (app()->environment('production')) {
                throw $e;
            } else {
                return false;
            }
        }
    }


    //Revisar cuando tengamos mail hecho, CartConfirm necesita service de mail.

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
            (new \App\Jobs\CartConfirm($cart, ['send_mail' => $send_mail, 'pdf' => brand_asset('base.inscription.ticket-web-params')]))->handle();
        } else {
            (new \App\Jobs\CartConfirm($cart, ['send_mail' => $send_mail, 'pdf' => brand_setting('base.inscription.ticket-office-params')]))->handle();
        }

        if ($send_mail) {
            Alert::success(trans('backpack::crud.regenerate_success_email'))->flash();
        } else {
            Alert::success(trans('backpack::crud.regenerate_success'))->flash();
        }


        return redirect()->route('cart.show', $cart);

    }


    public function changeGateway(Request $request, Cart $cart)
    {
        $payment = $cart->payment;
        $resp = json_decode($payment->gateway_response ?? '{}');
        $resp->payment_type = $request->input('gateway');

        $payment->gateway_response = json_encode($resp);
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
