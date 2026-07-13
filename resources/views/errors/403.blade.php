@include('errors.layout', [
    'code' => '403',
    'tone' => 'red',
    'visual' => 'shield',
    'badge' => 'ممنوع الوصول',
    'title' => 'ليست لديك صلاحية كافية',
    'message' => 'هذه الصفحة موجودة، لكن الحساب الحالي لا يملك الصلاحية المناسبة لعرضها.',
    'primaryText' => 'العودة للرئيسية',
    'primaryUrl' => url('/'),
    'primaryIcon' => 'home',
])
