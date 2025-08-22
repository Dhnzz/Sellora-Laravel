#!/usr/bin/env python3
import argparse, json, sys, os
import numpy as np
import pandas as pd
from datetime import datetime
from tensorflow.keras.models import load_model
import joblib


import sys
if sys.platform.startswith('win'):
    try:
        import asyncio
        asyncio.set_event_loop_policy(asyncio.WindowsSelectorEventLoopPolicy())
    except Exception:
        pass

def parse_args():
    p = argparse.ArgumentParser()
    p.add_argument('--series-json', required=True, help='Path to JSON: [{"date":"YYYY-MM-DD","value":123.45}, ...] ascending')
    p.add_argument('--model', required=True, help='Path to model.h5')
    p.add_argument('--scaler', required=True, help='Path to scaler.pkl')
    p.add_argument('--look-back', type=int, default=6)
    return p.parse_args()

def load_series(path):
    with open(path, 'r') as f:
        data = json.load(f)
    # Validasi minimal
    if not isinstance(data, list) or len(data) == 0:
        raise ValueError('series empty')
    df = pd.DataFrame(data)
    df['date'] = pd.to_datetime(df['date'])
    df = df.sort_values('date')
    return df

def build_sequences(scaled, look_back):
    X = []
    for i in range(look_back, len(scaled)+1):
        X.append(scaled[i-look_back:i, 0])
    return np.array(X)

def next_period(last_date):
    # asumsi cut-off akhir bulan
    year = last_date.year
    month = last_date.month
    if month == 12:
        return (year+1, 1)
    return (year, month+1)

def main():
    args = parse_args()

    # Load artefak
    if not os.path.exists(args.model):
        print('Model not found', file=sys.stderr); sys.exit(2)
    if not os.path.exists(args.scaler):
        print('Scaler not found', file=sys.stderr); sys.exit(2)

    model  = load_model(args.model)
    scaler = joblib.load(args.scaler)

    df = load_series(args.series_json)
    values = df['value'].astype(float).values.reshape(-1,1)

    # Scale pakai scaler dari training
    scaled = scaler.transform(values)
    look_back = args.look_back

    if len(scaled) < look_back:
        print('Not enough data for look_back', file=sys.stderr); sys.exit(3)

    last_seq = scaled[-look_back:].reshape(1, look_back, 1)
    pred_scaled = model.predict(last_seq, verbose=0)
    pred = scaler.inverse_transform(pred_scaled)[0,0]

    last_actual = float(values[-1,0])
    pct_change = (pred - last_actual) / last_actual * 100 if last_actual > 0 else None
    last_date = pd.to_datetime(df.iloc[-1]['date']).to_pydatetime()
    ny, nm = next_period(last_date)

    out = {
        "next_year": int(ny),
        "next_month": int(nm),
        "predicted_profit": float(round(pred, 2)),
        "meta": {
            "look_back": int(look_back),
            "last_actual": float(round(last_actual, 2)),
            "pct_change_vs_last": float(round(pct_change,2)) if pct_change is not None else None,
            "last_date": last_date.strftime('%Y-%m-%d'),
        }
    }
    print(json.dumps(out))
    sys.exit(0)

if __name__ == '__main__':
    main()
