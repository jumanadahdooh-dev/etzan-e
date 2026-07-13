@include('errors.layout', [
    'code' => '402',
    'tone' => 'teal',
    'visual' => 'card',
    'badge' => 'تفعيل مطلوب',
    'title' => 'هذه الخدمة تحتاج إلى تفعيل',
    'message' => 'قد تكون هذه الصفحة أو الخدمة مرتبطة بتفعيل إضافي أو صلاحية خاصة قبل استخدامها.',
    'primaryText' => 'العودة للرئيسية',
    'primaryUrl' => url('/'),
    'primaryIcon' => 'home',
])
