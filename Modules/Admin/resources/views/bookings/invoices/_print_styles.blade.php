/* Thermal Receipt Styles (58mm & 80mm) */
.thermal-receipt {
    background: #FFFFFF;
    color: #000000;
    font-family: "Courier New", Courier, monospace;
    margin: 0 auto;
    line-height: 1.35;
}
.thermal-receipt--80mm {
    width: 76mm;
    max-width: 100%;
    padding: 4mm 3mm;
    font-size: 11.5px;
}
.thermal-receipt--58mm {
    width: 54mm;
    max-width: 100%;
    padding: 3mm 2mm;
    font-size: 10px;
}
.tr-center {
    text-align: center;
}
.tr-biz-name {
    font-size: 1.15em;
    font-weight: 700;
    letter-spacing: 0.02em;
    margin-bottom: 2px;
}
.tr-muted-line {
    font-size: 0.9em;
    word-break: break-word;
}
.tr-divider {
    border-top: 1px dashed #000000;
    margin: 6px 0;
}
.tr-divider-solid {
    border-top: 1px solid #000000;
    margin: 5px 0;
}
.tr-kv {
    display: flex;
    justify-content: space-between;
    align-items: baseline;
    gap: 6px;
}
.tr-sec-title {
    font-weight: 700;
    font-size: 0.92em;
    letter-spacing: 0.04em;
    margin-bottom: 2px;
}
.tr-section {
    margin-bottom: 4px;
}
.tr-cat-title {
    font-weight: 700;
    font-size: 0.92em;
    text-transform: uppercase;
    margin-top: 4px;
    margin-bottom: 2px;
}
.tr-item {
    margin-bottom: 4px;
}
.tr-item-top {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 6px;
}
.tr-item-name {
    font-weight: 600;
    word-break: break-word;
    flex: 1 1 auto;
}
.tr-item-sub {
    font-weight: 400;
    font-size: 0.9em;
}
.tr-item-total {
    font-weight: 700;
    white-space: nowrap;
    flex-shrink: 0;
}
.tr-item-calc {
    font-size: 0.88em;
    padding-left: 4px;
}
.tr-grand-total {
    font-size: 1.15em;
    font-weight: 700;
    margin: 3px 0;
}
.tr-pay-line {
    font-size: 0.92em;
    margin-top: 2px;
}
.tr-thankyou {
    font-weight: 700;
    margin-bottom: 2px;
}

/* Normal A4 Invoice Styles */
.a4-invoice {
    background: #FFFFFF;
    color: #171717;
    width: 100%;
    max-width: 190mm;
    margin: 0 auto;
    padding: 10mm 10mm;
    font-size: 12.5px;
    line-height: 1.45;
}
.a4-header {
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
    gap: 16px;
    border-bottom: 2px solid #171717;
    padding-bottom: 12px;
    margin-bottom: 14px;
}
.a4-logo {
    max-height: 40px;
    max-width: 160px;
    object-fit: contain;
    margin-bottom: 6px;
    display: block;
}
.a4-biz-name {
    font-size: 16px;
    font-weight: 700;
    color: #171717;
}
.a4-biz-meta {
    font-size: 11.5px;
    color: #334155;
    margin-top: 2px;
}
.a4-title-block {
    text-align: right;
    flex-shrink: 0;
}
.a4-title {
    font-size: 22px;
    font-weight: 800;
    letter-spacing: 0.06em;
    color: #171717;
    margin-bottom: 6px;
}
.a4-meta-table {
    margin-left: auto;
    border-collapse: collapse;
    font-size: 11.5px;
}
.a4-meta-table th {
    text-align: right;
    padding: 2px 8px 2px 0;
    font-weight: 600;
    color: #475569;
}
.a4-meta-table td {
    text-align: right;
    padding: 2px 0;
    color: #171717;
}
.a4-info-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 14px;
    margin-bottom: 16px;
}
.a4-info-box {
    border: 1px solid #CBD5E1;
    border-radius: 6px;
    padding: 10px 12px;
    font-size: 12px;
}
.a4-box-title {
    font-size: 10.5px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.05em;
    color: #475569;
    margin-bottom: 5px;
}
.a4-info-strong {
    font-weight: 700;
    font-size: 13px;
    margin-bottom: 2px;
}
.a4-mt-sm {
    margin-top: 6px;
}
.a4-sched-row {
    margin-bottom: 3px;
}
.a4-sched-label {
    font-weight: 600;
    color: #475569;
    margin-right: 4px;
}
.a4-items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 16px;
    font-size: 12px;
}
.a4-items-table thead th {
    background-color: #F1F5F9;
    color: #171717;
    font-weight: 700;
    text-align: left;
    padding: 8px 10px;
    border-top: 1px solid #CBD5E1;
    border-bottom: 1px solid #94A3B8;
}
.a4-items-table th.text-right,
.a4-items-table td.text-right {
    text-align: right;
}
.a4-cat-row td {
    background-color: #F8FAFC;
    font-weight: 700;
    font-size: 11px;
    text-transform: uppercase;
    letter-spacing: 0.04em;
    padding: 6px 10px;
    border-bottom: 1px solid #E2E8F0;
    color: #334155;
}
.a4-item-row {
    break-inside: avoid;
    page-break-inside: avoid;
}
.a4-item-row td {
    padding: 8px 10px;
    border-bottom: 1px solid #E2E8F0;
    vertical-align: top;
}
.a4-item-name {
    font-weight: 600;
    color: #171717;
}
.a4-item-sub {
    font-size: 11px;
    color: #475569;
}
.a4-summary-wrap {
    display: flex;
    justify-content: flex-end;
    margin-bottom: 18px;
    break-inside: avoid;
    page-break-inside: avoid;
}
.a4-summary-table {
    width: 280px;
    border-collapse: collapse;
    font-size: 12px;
}
.a4-summary-table th {
    text-align: left;
    padding: 4px 8px 4px 0;
    font-weight: 500;
    color: #475569;
}
.a4-summary-table td {
    text-align: right;
    padding: 4px 0;
    font-weight: 600;
    color: #171717;
}
.a4-final-row th,
.a4-final-row td {
    border-top: 2px solid #171717;
    border-bottom: 2px solid #171717;
    padding: 7px 0;
    font-size: 14px;
    font-weight: 800;
    color: #171717;
}
.a4-footer {
    border-top: 1px solid #CBD5E1;
    padding-top: 10px;
    text-align: center;
    font-size: 11.5px;
    color: #475569;
    break-inside: avoid;
    page-break-inside: avoid;
}
.a4-footer-thanks {
    font-weight: 700;
    color: #171717;
    margin-bottom: 2px;
}
