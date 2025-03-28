<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Varsayılan Laravel Doğrulama Dil Satırları
    |--------------------------------------------------------------------------
    |
    | Aşağıdaki dil satırları, Laravel doğrulayıcı sınıfı tarafından kullanılan
    | varsayılan hata mesajlarını içerir. Boyut kuralları gibi bazı kuralların
    | birden fazla versiyonu olabilir. Bu mesajları ihtiyacınıza göre düzenleyebilirsiniz.
    |
    */
    'file' => ':attribute bir dosya olmalıdır.',
    'image' => ':attribute bir resim olmalıdır.',
    'required' => ':attribute alanı gereklidir.',
    'max' => [
        'array' => ':attribute alanı en fazla :max öğe içerebilir.',
        'file' => ':attribute alanı en fazla :max kilobayt olmalıdır.',
        'numeric' => ':attribute alanı en fazla :max olmalıdır.',
        'string' => ':attribute alanı en fazla :max karakter olmalıdır.',
    ],
    'mimes' => ':attribute şu türde bir dosya olmalıdır: :values.',

    /*
    |--------------------------------------------------------------------------
    | Özel Doğrulama Dil Satırları
    |--------------------------------------------------------------------------
    |
    | Burada, belirli bir doğrulama kuralı için özel hata mesajları belirtebilirsiniz.
    | "attribute.rule" adlandırma kuralını kullanarak özel mesajlar tanımlayabilirsiniz.
    |
    */

    'custom' => [],

];
