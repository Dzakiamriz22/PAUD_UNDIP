<style>
    /* Compact professional invoice styles for A4 portrait */
    @page { size: A4 portrait; margin: 12mm; }

    body.payment-doc {
        font-family: DejaVu Sans, Arial, sans-serif;
        color: #111827;
        font-size: 11px;
        line-height: 1.15;
        margin: 0;
        padding: 0;
    }

    /* Header */
    .payment-header {
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 6px;
        margin-bottom: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 8px;
    }

    .payment-logo img { max-width: 88px; height: auto; display:block }
    .payment-school { font-size: 13px; font-weight:700; margin:0 }
    .payment-subtitle { font-size: 11px; font-weight:600; margin:0; }

    .payment-title { text-align: right; }
    .payment-title h2 { margin:0; font-size:16px; font-weight:700 }

    /* address aligned left, larger and bold */
    .payment-address { font-size:14px; font-weight:700; color:#374151; padding-top:4px; text-align:left; }

    /* dark mode overrides for header elements */
    .dark .payment-school,
    .dark .payment-subtitle,
    .dark .payment-title h2,
    .dark .payment-address {
        color: #ffffff;
    }
    /* dark mode table borders remain light so they don’t overpower black background */
    .dark table.payment th, .dark table.payment td { border-color: #e6e9ee }
    .status-unpaid { color: #dc2626; font-weight:700; font-size:12px }
    .status-paid { color: #16a34a; font-weight:700; font-size:12px }

    /* Info rows */
    .info-table { width:100%; margin-bottom:8px; font-size:11px }
    .info-table td { padding:2px 0; vertical-align: top }

    /* Items table */
    table.payment { width:100%; border-collapse: collapse; margin-bottom:8px; font-size:11px }
    /* use solid black borders in light/white mode for clarity */
    table.payment th, table.payment td { border:1px solid #000; padding:6px 6px }
    table.payment th { text-align:left; font-weight:600; font-size:11px }
    table.payment td { vertical-align: middle }
    table.payment td.amount { text-align:right; white-space:nowrap }

    /* Condensed rows for large item lists */
    table.payment tbody tr { height: auto }
    table.payment td, table.payment th { padding-top:5px; padding-bottom:5px }

    .total-row th, .total-row td { font-weight:700; background:#fafafa; font-size:12px }

    /* Signature area — smaller to save space */
    .signature { width:34%; float:right; text-align:center; margin-top:8px }
    .signature .name { margin-top:28px; font-weight:700; font-size:12px }
    .clear{ clear:both }

    /* Keep one logical invoice per page */
    .page { page-break-after: always }

    /* Utility */
    .small { font-size:10px; color:#374151 }
    .muted { color:#6b7280 }
    /* Discount styling: italic muted text, negative amount kept red */
    tr.discount { color: #6b7280; font-style: italic }
    tr.discount td { color: #6b7280; font-style: italic }
    tr.discount td.amount { color: #6b7280; font-weight:700; font-style: italic }
    /* removed badge usage: keep class for backward compatibility */
    .badge-discount { display:none }
</style>
