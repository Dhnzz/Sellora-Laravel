<?php
return [
    'flask_url' => env('FLASK_PRED_URL', 'http://127.0.0.1:5000'),
    'pred_token' => env('PRED_TOKEN'),
    'fp_token'   => env('FP_API_KEY'),
    'model_version' => env('MODEL_VERSION', 'lstm_v1'),
    'look_back' => (int) env('LSTM_LOOK_BACK', 6),

    // cashflow planning
    'procurement_ratio' => (float) env('PROCUREMENT_RATIO', 0.55),
    'abc_base' => array_map('intval', explode(',', env('ABC_BASE', '60,30,10'))),
    'cashflow_adj_cap' => (float) env('CASHFLOW_ADJ_CAP', 0.2),
];
