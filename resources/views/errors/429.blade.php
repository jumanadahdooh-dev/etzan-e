@include('errors.layout', [
    'code' => '429',
    'tone' => 'blue',
    'visual' => 'lock',
    'badge' => 'محاولات كثيرة',
    'title' => 'عدد المحاولات تجاوز الحد المسموح',
    'message' => 'لحماية حسابك أوقفنا المحاولات مؤقتًا. انتظر دقيقة ثم حاول مرة أخرى.',
    'primaryText' => 'العودة لتسجيل الدخول',
    'primaryUrl' => route('login'),
    'primaryIcon' => 'login',
    'secondaryText' => 'العودة للرئيسية',
    'secondaryUrl' => url('/'),
])
