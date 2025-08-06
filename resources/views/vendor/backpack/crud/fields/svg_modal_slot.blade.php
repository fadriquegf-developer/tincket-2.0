<div class="modal fade" tabindex="-1" role="dialog" id="setSlotProperties">
  <div class="modal-dialog" role="document">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
        <h4 class="modal-title">{{ trans('backend.svg_layout.model_slot.title') }}</h4>
      </div>

      <div class="modal-body row">
        <div class="form-group col-sm-12">
          <label class="control-label">{{ trans('backend.svg_layout.model_slot.slot_id') }}:</label>
          <input type="text" class="form-control" id="set-slot-id" disabled>
        </div>

        <div class="form-group col-sm-6">
          <label class="control-label">{{ trans('backend.svg_layout.model_slot.x') }}:</label>
          <input type="number" class="form-control" id="set-slot-x" name="x">
        </div>
        <div class="form-group col-sm-6">
          <label class="control-label">{{ trans('backend.svg_layout.model_slot.y') }}:</label>
          <input type="number" class="form-control" id="set-slot-y" name="y">
        </div>

        <div class="form-group col-sm-12">
          <label class="control-label">{{ trans('backend.svg_layout.model_slot.name') }}:</label>
          <input type="text" class="form-control" id="set-slot-name">
        </div>

        <div class="form-group col-sm-12">
          <label class="control-label">{{ trans('backend.svg_layout.model_slot.status') }}:</label>
          <select class="form-control" id="set-slot-status">
            <option value="null">Disponible</option>
            @foreach(\App\Models\Status::where('id', '!=', 6)->get() as $status)
        <option value="{{ $status->id }}">{{ $status->name }}</option>
      @endforeach
          </select>
        </div>

        <div class="form-group col-sm-12">
          <label class="control-label">{{ trans('backend.svg_layout.model_slot.zone') }}:</label>
          <select class="form-control" id="set-slot-zone">
            <option value="">-- {{ __('backend.svg_layout.model_slot.zone_select') }} --</option>
          </select>
        </div>

        <div class="form-group col-sm-12">
          <label class="control-label">{{ trans('backend.svg_layout.model_slot.comment') }}</label>
          <input name="comment" class="form-control" />
        </div>
      </div>

      <div class="modal-footer">
        <div class="row">
          <div class="col-xs-9">
            <p class="small">{{ trans('backend.svg_layout.model_slot.help') }}</p>
          </div>
          <div class="col-xs-3">
            <button type="button"
              class="btn btn-block btn-primary btn-set">{{ trans('backend.svg_layout.model_slot.btn') }}</button>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>