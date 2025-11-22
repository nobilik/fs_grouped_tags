<div class="modal fade" id="editGroupModal-{{ $group->id }}" tabindex="-1" role="dialog" aria-labelledby="editGroupModalLabel-{{ $group->id }}">
    <div class="modal-dialog modal-sm" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="editGroupModalLabel-{{ $group->id }}">{{ __('Edit Tag Group') }}: {{ $group->name }}</h4>
            </div>
            {{-- Используем PUT-метод для обновления --}}
            <form action="{{ route('grouped-tags.update', ['group' => $group->id]) }}" method="POST">
                <input type="hidden" name="_token" value="{{ csrf_token() }}">
                <input type="hidden" name="_method" value="PUT">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="name-{{ $group->id }}">{{ __('Group Name') }}</label>
                        <input type="text" class="form-control" name="name" id="name-{{ $group->id }}" value="{{ $group->name }}" required>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="copy_to_new_conversation" value="1" 
                                @if($group->copy_to_new_conversation) checked @endif> 
                            {{ __('Copy tags from this group to new conversations created by the same customer.') }}
                        </label>
                    </div>

                    <div class="form-group">
                        <label class="checkbox-inline">
                            <input type="checkbox" name="required_for_conversation" value="1" 
                                @if($group->required_for_conversation) checked @endif> 
                            {{ __('Tag from this group is required for a conversation.') }}
                        </label>
                    </div>

                    <div class="form-group">
                        <label for="max_tags_for_conversation-{{ $group->id }}">{{ __('Max Tags For Conversation (N)') }}</label>
                        <input type="number" class="form-control" name="max_tags_for_conversation" id="max_tags_for_conversation-{{ $group->id }}" min="{{ 0 }}" value="{{ $group->max_tags_for_conversation }}" required>
                        <p class="help-block">{{ __('Zero value is unlimited.') }}</p>
                    </div>

                    <div class="form-group">
                        <label for="max_tags-{{ $group->id }}">{{ __('Max Tags (N)') }}</label>
                        <input type="number" class="form-control" name="max_tags" id="max_tags-{{ $group->id }}" min="{{ $group->tags->count() }}" value="{{ $group->max_tags }}" required>
                        <p class="help-block">{{ __('Current tags:') }} {{ $group->tags->count() }}. {{ __('Minimum value is current count.') }}</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn-primary">{{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>