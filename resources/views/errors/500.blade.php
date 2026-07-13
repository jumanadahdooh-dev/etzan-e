@include('errors.layout', [
    'code' => '500',
    'tone' => 'red',
    'visual' => 'server',
    'badge' => 'خطأ داخلي',
    'title' => 'حدث خطأ داخل النظام',
    'message' => 'حدثت مشكلة غير متوقعة أثناء تنفيذ الطلب. يرجى المحاولة مرة أخرى بعد قليل.',
    'primaryText' => 'إعادة المحاولة',
    'primaryUrl' => 'javascript:location.reload()',
    'primaryIcon' => 'refresh',
    'secondaryText' => 'العودة للرئيسية',
    'secondaryUrl' => url('/'),
])
