<div class="modal fade" id="addTagModal" tabindex="-1" role="dialog" aria-labelledby="addTagModalLabel">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form method="POST" action="{{ route('grouped-tags.tag.store') }}">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title" id="addTagModalLabel">{{ __('Create New Tag') }}</h4>
                </div>
                <div class="modal-body">
                    
                    {{-- Поле имени тега --}}
                    <div class="form-group">
                        <label for="tag_name">{{ __('Tag Name') }}</label>
                        <input type="text" class="form-control" id="tag_name" name="tag_name" required maxlength="255">
                    </div>

                    {{-- Поле цвета тега (Выпадающий список ID) --}}
                    <div class="form-group">
                        <label for="tag_color">{{ __('Tag Color ID') }}</label>
                        <select class="form-control" id="tag_color" name="tag_color" required>
                            <option value="0">0 - Default</option>
                            <option value="1">1 - Green</option>
                            <option value="2">2 - Blue</option>
                            <option value="3">3 - Orange</option>
                            <option value="4">4 - Violet</option>
                            <option value="5">5 - Red</option>
                            <option value="6">6 - Brown</option>
                            <option value="7">7 - Yellow</option>
                            <option value="8">8 - Pink</option>
                            <option value="9">9 - Lime</option>
                            <option value="10">10 - Turquoise</option>
                            <option value="11">11 - Lavender</option>
                        </select>
                        <span class="help-block">{{ __('Select the color ID (0-11) for the tag.') }}</span>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Cancel') }}</button>
                    <button type="submit" class="btn btn-success">{{ __('Create Tag') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>