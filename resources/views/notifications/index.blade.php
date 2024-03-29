@extends('layouts.layout')

@section('title')
{{__('Notifications Inbox')}}
@endsection

@section('sidebar')
@include('layouts.sidebar', ['sidebar' => Menu::get('sidebar_notifications')])
@endsection

@section('breadcrumbs')
@include('shared.breadcrumbs', ['routes' => [
  $title => null,
]])
@endsection
@section('content')
<div class="px-3 page-content" id="notifications">
    <div class="container-fluid">
      <b-tabs>
      <b-tab title="{{ __('All') }}" @click="setFilterComments(null)">
        </b-tab>
        <b-tab title="{{ __('Inbox') }}" @click="setFilterComments('hide')">
        </b-tab>
        <b-tab title="{{ __('Comments') }}" @click="setFilterComments('show')">
        </b-tab>
      </b-tabs>

      <div class="card card-body table-card">
        <div id="search-bar" class="search mb-3" vcloak style="margin: 16px;">
          <div class="d-flex flex-column flex-md-row">
            <div class="flex-grow-1">
              <div id="search" class="mb-3 mb-md-0">
                <div class="input-group w-100">
                  <input id="search-box" v-model="filter" class="form-control" placeholder="{{__('Search')}}"  aria-label="{{__('Search')}}">
                  <div class="input-group-append">
                    <button type="button" class="btn btn-primary" aria-label="{{__('Search')}}"><i class="fas fa-search"></i></button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <notifications-list :type="null" :filter="filter" :filter-comments="filterComments" style="margin: 16px;" />
      </div>
    </div>
</div>
@endsection

@section('js')
<script src="{{mix('js/notifications/index.js')}}"></script>
@endsection
