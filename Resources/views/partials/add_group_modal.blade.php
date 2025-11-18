<div class="modal fade" id="addGroupModal" tabindex="-1" role="dialog" aria-labelledby="addGroupModalLabel">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
                <h4 class="modal-title" id="addGroupModalLabel">{{ __('Add New Tag Group') }}</h4>
            </div>        
            <form action="{{ route('grouped-tags.store') }}" method="POST">
            <input type="hidden" name="_token" value="{{ csrf_token() }}">

            <div class="modal-body">
                <div class="form-group">
                    <label for="name">{{ __('Group Name') }}</label>
                    <input type="text" class="form-control" name="name" id="name" required>
                </div>
                
                <div class="form-group">
                    <label for="max_tags">{{ __('Max Tags (N)') }}</label>
                    <input type="number" class="form-control" name="max_tags" id="max_tags" min="1" value="10" required>
                    <p class="help-block">{{ __('Maximum number of tags allowed in this group.') }}</p>
                </div>

                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="copy_to_new_conversation" value="1" checked> {{ __('Copy Tags') }}
                    </label>
                    <p class="help-block">{{ __('If checked, tags from this group will be copied to new conversations from the same customer.') }}</p>
                </div>

                <div class="form-group">
                    <label class="checkbox-inline">
                        <input type="checkbox" name="auto_apply" value="1"> 
                        {{ __('Automatically apply tags from this group.') }}
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Close') }}</button>
                <button type="submit" class="btn btn-primary">{{ __('Save') }}</button>
            </div>
        </form>
    </div>
</div>
</div>