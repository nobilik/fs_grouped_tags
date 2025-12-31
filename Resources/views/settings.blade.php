@extends('layouts.app')

@section('title_full', __('Grouped Tags Settings'))

@section('content')

<div class="section-heading">
<h2><i class="glyphicon glyphicon-tags"></i> {{ __('Grouped Tags Settings') }}</h2>
</div>

<p>{{ __('Here you can manage groups for tags, set limits, and define rules for automatic application.') }}</p>

{{-- БЛОК ВЫВОДА FLASH-СООБЩЕНИЙ --}}
@if(session('success'))
<div class="alert alert-success">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
<i class="glyphicon glyphicon-ok"></i>
{{ session('success') }}
</div>
@endif

@if(session('error'))
<div class="alert alert-danger">
<button type="button" class="close" data-dismiss="alert" aria-hidden="true">&times;</button>
<i class="glyphicon glyphicon-remove"></i>
{{ session('error') }}
</div>
@endif
{{-- Конец блока вывода сообщений --}}

<div class="row">

{{-- ========================================================= --}}
{{-- СЕКЦИЯ 1: УПРАВЛЕНИЕ ГРУППАМИ --}}
{{-- ========================================================= --}}

<div class="col-md-7">
<h3>{{ __('Tag Groups') }} ({{ count($groups) }})</h3>

<button type="button" class="btn btn-primary btn-sm pull-right" data-toggle="modal" data-target="#addGroupModal">
    <i class="glyphicon glyphicon-plus"></i> {{ __('Add New Group') }}
</button>

<div class="clearfix"></div>
<br>

@foreach($groups as $group)
    <div class="panel panel-default" data-group-id="{{ $group->id }}">
        <div class="panel-heading">
            <h4 class="panel-title">
                {{ $group->name }} ({{ $group->tags->count() }}/{{ $group->max_tags }} {{ __('tags') }})
                <div class="btn-group pull-right">
                    <button type="button" class="btn btn-xs btn-default" 
                            data-toggle="modal" data-target="#editGroupModal-{{ $group->id }}">
                        <i class="glyphicon glyphicon-pencil"></i>
                    </button>
                    {{-- Обходной путь: URL для удаления передается через data-атрибут --}}
                    <button type="button" class="btn btn-xs btn-danger js-delete-group" 
                            data-group-id="{{ $group->id }}" 
                            data-group-name="{{ $group->name }}"
                            data-delete-url="{{ route('grouped-tags.destroy', ['group' => $group->id]) }}"> 
                        <i class="glyphicon glyphicon-trash"></i>
                    </button>
                </div>
            </h4>
        </div>
        <div class="panel-body">
            @if($group->copy_to_new_conversation)
                <span class="label label-info"><i class="glyphicon glyphicon-repeat"></i> {{ __('Copy to New Conversation') }}</span>
            @else
                <span class="label label-default">{{ __('Do not Copy') }}</span>
            @endif

            @if($group->required_for_conversation)
                <span class="label label-success"><i class="glyphicon glyphicon-flash"></i> {{ __('Required Tags') }}</span>
            @else
                <span class="label label-default">{{ __('Free Tags') }}</span>
            @endif
            <hr>
            
            <h5>{{ __('Group Tags') }}:</h5>
            @forelse($group->tags as $tag)
                <div class="btn-group" style="margin-bottom: 5px;">
                    <span class="btn btn-sm btn-default tag-name tag-c-{{ $tag->getColor() }}">{{ $tag->name }}</span>
                    {{-- data-атрибуты для открепления --}}
                    <button class="btn btn-sm btn-danger js-detach-tag" title="{{ __('Detach Tag') }}" 
                            data-group-id="{{ $group->id }}" 
                            data-tag-id="{{ $tag->id }}"
                            data-group-name="{{ $group->name }}"
                            data-tag-name="{{ $tag->name }}"
                            data-detach-url="{{ route('grouped-tags.detach') }}">
                        &times;
                    </button>
                </div>
            @empty
                <p class="text-muted">{{ __('No tags assigned to this group yet.') }}</p>
            @endforelse
        </div>
    </div>
    
    @include('nobilikgroupedtags::partials.edit_group_modal', ['group' => $group])
@endforeach

@include('nobilikgroupedtags::partials.add_group_modal')
@include('nobilikgroupedtags::partials.add_tag_modal')

<form id="delete-group-form" method="POST" style="display: none;">
    @csrf
    @method('DELETE')
</form>

</div>

{{-- ========================================================= --}}
{{-- СЕКЦИЯ 2: ДОСТУПНЫЕ ТЕГИ (для привязки) --}}
{{-- ========================================================= --}}

<div class="col-md-5">
<h3>{{ __('Available Tags') }} ({{ count($available_tags) }})</h3>
<button type="button" class="btn btn-success btn-sm pull-right" data-toggle="modal" data-target="#addTagModal">
        <i class="glyphicon glyphicon-plus"></i> {{ __('Create New Tag') }}
</button>
<p class="text-muted">{{ __('These tags do not belong to any group and can be assigned.') }}</p>

<div class="panel panel-default">
    <div class="panel-body">
        @forelse($available_tags as $tag)
            <div class="dropdown" style="display: inline-block; margin-bottom: 5px;">
                <button class="btn btn-sm btn-success dropdown-toggle tag-c-{{ $tag->getColor() }}" type="button" data-toggle="dropdown">
                    {{ $tag->name }} <span class="caret"></span>
                </button>
                <ul class="dropdown-menu">
                    @foreach($groups as $group)
                        @if($group->tags->count() < $group->max_tags) 
                            <li>
                                {{-- data-атрибуты для прикрепления --}}
                                <a href="#" class="js-attach-tag" 
                                   data-group-id="{{ $group->id }}" 
                                   data-tag-id="{{ $tag->id }}"
                                   data-attach-url="{{ route('grouped-tags.attach') }}"
                                   title="{{ __('Add to Group') }}: {{ $group->name }}">
                                    {{ __('Add to Group') }}: <strong>{{ $group->name }}</strong>
                                </a>
                            </li>
                        @endif
                    @endforeach
                </ul>
            </div>
        @empty
            <p class="text-success">{{ __('All existing tags are assigned to a group.') }}</p>
        @endforelse
    </div>
</div>

</div>
</div>
@endsection

@push('javascript')

@endpush