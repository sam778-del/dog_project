<div class="sidebar p-2 py-md-3">
    <div class="container-fluid">
       <!-- sidebar: menu list -->
       <div class="main-menu flex-grow-1">
          <ul class="menu-list">
            <li>
               <a class="m-link {{ Request::segment(1) == "" ? 'active' : '' }}" href="{{ url('/') }}">
                  <span class="ms-2">{{ __('Schedule') }}</span>
               </a>
            </li>
             <li>
                <a class="m-link {{ Request::segment(1) == "racing" ? 'active' : '' }}" href="{{ route('racing.index') }}">
                   <span class="ms-2">{{ __('All Dogs') }}</span>
                </a>
             </li>
          </ul>
       </div>
    </div>
 </div>
