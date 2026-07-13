@include('errors.layout', [
    'code' => '400',
    'tone' => 'red',
    'visual' => 'alert',
    'badge' => 'طلب غير صحيح',
    'title' => 'الطلب غير مكتمل',
    'message' => 'يبدو أن البيانات المرسلة غير صحيحة أو أن الطلب لم يصل بالشكل المتوقع. يرجى الرجوع والمحاولة مرة أخرى.',
    'primaryText' => 'العودة للرئيسية',
    'primaryUrl' => url('/'),
    'primaryIcon' => 'home',
])
