@include('errors.layout', [
    'code' => '503',
    'tone' => 'green',
    'visual' => 'tools',
    'badge' => 'وضع الصيانة',
    'title' => 'الموقع قيد التحديث حاليًا',
    'message' => $message ?? 'نعمل حاليًا على تحسين الموقع، يرجى المحاولة لاحقًا.',
    'primaryText' => 'إعادة التحميل',
    'primaryUrl' => 'javascript:location.reload()',
    'primaryIcon' => 'refresh',
    'secondaryText' => 'العودة للرئيسية',
    'secondaryUrl' => url('/'),
])
