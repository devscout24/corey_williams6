@extends('layouts.app')

@section('title', $tag ? 'Edit Tag' : 'New Tag')
@section('page-title', $tag ? 'Edit Tag' : 'New Tag')

@section('content')
<div class="container-fluid">
    <form method="post" action="{{ $tag ? route('tags.update', $tag->id) : route('tags.store') }}" class="card p-4 shadow-sm">
        @csrf
        @if($tag)
            @method('put')
        @endif

        <div class="mb-3">
            <label class="form-label" for="tag_name">Tag Name</label>
            <input type="text" class="form-control" id="tag_name" name="tag_name" 
                   value="{{ old('tag_name', $tag?->name) }}" required 
                   maxlength="255">
        </div>

        <div class="d-flex justify-content-end gap-2">
            <a href="{{ route('tags.index') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" class="btn btn-primary">Save Tag</button>
        </div>
    </form>
</div>
@endsection
