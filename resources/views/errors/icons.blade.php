@php
    $name = $name ?? 'alert';
@endphp

@if($name === 'search')
    <svg viewBox="0 0 24 24" fill="none">
        <path d="M10.8 18.1C14.8 18.1 18.1 14.8 18.1 10.8C18.1 6.8 14.8 3.5 10.8 3.5C6.8 3.5 3.5 6.8 3.5 10.8C3.5 14.8 6.8 18.1 10.8 18.1Z" stroke="currentColor" stroke-width="2"/>
        <path d="M16.2 16.2L21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
@elseif($name === 'lock')
    <svg viewBox="0 0 24 24" fill="none">
        <path d="M7 10V8C7 5.2 9.2 3 12 3C14.8 3 17 5.2 17 8V10" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M6 10H18C19.1 10 20 10.9 20 12V20H4V12C4 10.9 4.9 10 6 10Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    </svg>
@elseif($name === 'card')
    <svg viewBox="0 0 24 24" fill="none">
        <path d="M3.5 7.5C3.5 6.4 4.4 5.5 5.5 5.5H18.5C19.6 5.5 20.5 6.4 20.5 7.5V16.5C20.5 17.6 19.6 18.5 18.5 18.5H5.5C4.4 18.5 3.5 17.6 3.5 16.5V7.5Z" stroke="currentColor" stroke-width="2"/>
        <path d="M3.5 9.5H20.5" stroke="currentColor" stroke-width="2"/>
    </svg>
@elseif($name === 'shield')
    <svg viewBox="0 0 24 24" fill="none">
        <path d="M12 21C12 21 19.5 17.5 19.5 10.2V5.5L12 3L4.5 5.5V10.2C4.5 17.5 12 21 12 21Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
        <path d="M9.5 12L11.2 13.7L15 9.8" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
@elseif($name === 'server')
    <svg viewBox="0 0 24 24" fill="none">
        <path d="M4.5 5H19.5V10H4.5V5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
        <path d="M4.5 14H19.5V19H4.5V14Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
        <path d="M8 7.5H8.1" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
        <path d="M8 16.5H8.1" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
    </svg>
@elseif($name === 'tools')
    <svg viewBox="0 0 24 24" fill="none">
        <path d="M14.5 6.5L17.5 3.5L20.5 6.5L17.5 9.5L14.5 6.5Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
        <path d="M16.5 8.5L8 17C7.2 17.8 5.9 17.8 5.1 17C4.3 16.2 4.3 14.9 5.1 14.1L13.5 5.7" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
@elseif($name === 'home')
    <svg viewBox="0 0 24 24" fill="none">
        <path d="M4 11.5L12 4L20 11.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M6.5 10.5V20H17.5V10.5" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
    </svg>
@elseif($name === 'login')
    <svg viewBox="0 0 24 24" fill="none">
        <path d="M10 7V5H20V19H10V17" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
        <path d="M4 12H14" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M11 9L14 12L11 15" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
@elseif($name === 'refresh')
    <svg viewBox="0 0 24 24" fill="none">
        <path d="M20 12C20 16.4 16.4 20 12 20C8.8 20 6.1 18.1 4.8 15.4" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M4 12C4 7.6 7.6 4 12 4C15.2 4 17.9 5.9 19.2 8.6" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M19.5 4.8V8.8H15.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
    </svg>
@elseif($name === 'back')
    <svg viewBox="0 0 24 24" fill="none">
        <path d="M15 5L8 12L15 19" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
        <path d="M9 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
@else
    <svg viewBox="0 0 24 24" fill="none">
        <path d="M12 3L21 19H3L12 3Z" stroke="currentColor" stroke-width="2" stroke-linejoin="round"/>
        <path d="M12 9V13" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M12 17H12.01" stroke="currentColor" stroke-width="3" stroke-linecap="round"/>
    </svg>
@endif
