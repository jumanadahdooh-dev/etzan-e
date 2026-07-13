@include('errors.layout', [
    'code' => '404',
    'tone' => 'blue',
    'visual' => 'search',
    'badge' => 'الصفحة غير موجودة',
    'title' => 'لم نتمكن من العثور على الصفحة',
    'message' => 'الرابط الذي تحاولين الوصول إليه غير موجود أو تم تغييره أو حذفه من الموقع.',
    'primaryText' => 'العودة للرئيسية',
    'primaryUrl' => url('/'),
    'primaryIcon' => 'home',
])
