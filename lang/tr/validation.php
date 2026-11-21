<?php

return [
    'accepted' => ':attribute kabul edilmelidir.',
    'active_url' => ':attribute geçerli bir URL olmalıdır.',
    'after' => ':attribute şundan daha eski bir tarih olmalıdır :date.',
    'alpha' => ':attribute sadece harflerden oluşmalıdır.',
    'alpha_dash' => ':attribute sadece harfler, rakamlar ve tirelerden oluşmalıdır.',
    'alpha_num' => ':attribute sadece harfler ve rakamlar içerebilir.',
    'array' => ':attribute dizi olmalıdır.',
    'before' => ':attribute şundan daha önceki bir tarih olmalıdır :date.',
    'between' => [
        'numeric' => ':attribute :min - :max arasında olmalıdır.',
        'file' => ':attribute :min - :max arasındaki kilobayt değeri olmalıdır.',
        'string' => ':attribute :min - :max karakter sayısı arasında olmalıdır.',
        'array' => ':attribute :min - :max arasında nesneye sahip olmalıdır.',
    ],
    'boolean' => ':attribute alanı sadece doğru veya yanlış olabilir.',
    'confirmed' => ':attribute tekrarı eşleşmiyor.',
    'email' => ':attribute geçerli bir e-posta adresi olmalıdır.',
    'max' => [
        'numeric' => ':attribute değeri :max değerinden küçük olmalıdır.',
        'file' => ':attribute değeri :max kilobayt değerinden küçük olmalıdır.',
        'string' => ':attribute değeri :max karakter değerinden küçük olmalıdır.',
        'array' => ':attribute değeri :max adedinden az nesneye sahip olmalıdır.',
    ],
    'min' => [
        'numeric' => ':attribute değeri :min değerinden büyük olmalıdır.',
        'file' => ':attribute değeri :min kilobayt değerinden büyük olmalıdır.',
        'string' => ':attribute değeri :min karakter değerinden büyük olmalıdır.',
        'array' => ':attribute en az :min nesneye sahip olmalıdır.',
    ],
    'required' => ':attribute alanı zorunludur.',
    'unique' => ':attribute daha önceden kayıt edilmiş.',
];
