<?php

namespace App\Http\Controllers\Admin;

use App\Models\Code;
use App\Models\Brand;
use App\Models\Setting;
use App\Traits\CrudPermissionTrait;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

class CodeCrudController extends CrudController
{

  use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
  use CrudPermissionTrait;
  use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation { destroy as traitDestroy;
    }


  public function setup()
  {
    CRUD::setModel(Code::class);
    CRUD::setRoute(config('backpack.base.route_prefix') . '/code');
    CRUD::setEntityNameStrings('code', 'codes');
    $this->setAccessUsingPermissions();

    CRUD::orderBy('created_at', 'desc');


    CRUD::addButtonFromModelFunction('top', 'generate_code', 'generateCodeButton', 'beginning');
    CRUD::addButtonFromView('line', 'info_promotor', 'info_promotor', 'beginning');
    CRUD::addButtonFromView('line', 'login_promotor', 'login_promotor', 'beginning');
  }



  protected function setupListOperation()
  {
    CRUD::addColumn(['name' => 'keycode', 'label' => __('backend.code.keycode')]);
    CRUD::addColumn(['name' => 'brand_name', 'label' => __('backend.code.brand_name')]);
    CRUD::addColumn(['name' => 'promotor_name', 'label' => __('backend.code.promotor_name')]);
  }


  public function generateCode()
  {
    $code = new Code;
    $code->keycode = $code->generateRandomString();
    $code->brand_id = get_current_brand()->id;
    $code->save();

    return back();
  }

  public function infoPromotor($promotor_id)
  {
    $promotor = Brand::find($promotor_id);
    $crud = $this->crud;

    return view('vendor.backpack.crud.info_promotor', compact('promotor', 'crud'));
  }

  public function storeInfoPromotor(\Illuminate\Http\Request $request)
  {
    $promotor = Brand::find($request->promotor_id);
    $promotor->comment = $request->comment;
    $promotor->save();

    return redirect('/code');
  }

  /**
   * Remove the specified resource from storage.
   *
   * @param int $id
   *
   * @return string
   */
  public function destroy($id)
  {
    CRUD::hasAccessOrFail('delete');

    // remove partner
    $entry = $this->crud->getEntry($id);

    if ($promotor = $entry->promotor) {
      $primary_brand = get_current_brand();
      $brand_partners_ids = Setting::where('brand_id', $primary_brand->id)->where('key', 'base.brand.partnershiped_ids')->first();

      $aux = explode(',', $brand_partners_ids->value);

      if (($key = array_search($promotor->id, $aux)) !== false) {
        unset($aux[$key]);
      }

      Setting::where('brand_id', $primary_brand->id)->where('key', 'base.brand.partnershiped_ids')->update(['value' => implode(',', $aux)]);
    }

    return $this->traitDestroy($id);
  }
}
