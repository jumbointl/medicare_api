<?php

return [
    'default' => (int) env('LIMIT_QTY', 20),
    'max' => (int) env('LIMIT_QTY_MAX', 100),
];