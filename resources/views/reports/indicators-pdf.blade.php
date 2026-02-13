<!doctype html>
@php($orgName = \App\Models\AdminSetting::query()->value('org_name') ?: 'DOST Tracker')
<html lang="en" class="theme-dark dark">
<head>
  <meta charset="utf-8">
  <title>{{ $orgName }} — Indicators Report</title>
  <style>
    body { font-family: ui-sans-serif,system-ui,Segoe UI,Roboto,Helvetica,Arial,Apple Color Emoji,Segoe UI Emoji; font-size: 12px; color: #111827; }
    h1 { margin: 0 0 4px; font-size: 20px; }
    .muted { color: #6b7280; font-size: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 16px; }
    th, td { border: 1px solid #e5e7eb; padding: 6px 8px; vertical-align: top; }
    th { background: #f3f4f6; text-transform: uppercase; font-size: 11px; color: #374151; }
    .small { font-size: 11px; color: #374151; }
    @media print {
      @page { margin: 16mm; }
      a { color: inherit; text-decoration: none; }
    }
  </style>
</head>
<body>
  <h1>{{ $orgName }} — Indicators Report</h1>
  <div class="muted">Printed: {{ $printed->format('Y-m-d H:i') }}</div>

  <table>
    <thead>
      <tr>
        <th>ID</th>
        <th>Objective / Result</th>
        <th>Indicator</th>
        <th>Description</th>
        <th>DOST Agency</th>
        <th>Baseline</th>
        <th>Accomp.</th>
        <th>Annual Target</th>
        <th>Target Period</th>
        <th>Target Value</th>
        <th>MOV</th>
        <th>Responsible</th>
        <th>Reporting</th>
      </tr>
    </thead>
    <tbody>
      @forelse($rows as $r)
        <tr>
          <td class="small">{{ $r->id }}</td>
          <td><strong>{{ $r->objective_result }}</strong></td>
          <td>{{ $r->indicator }}</td>
          <td>{{ $r->description }}</td>
          <td>{{ $r->dost_agency }}</td>
          <td>{{ $r->baseline }}</td>
          <td>{{ $r->accomplishments }}</td>
          <td>{{ $r->annual_plan_targets }}</td>
          <td>{{ $r->target_period }}</td>
          <td>{{ $r->target_value }}</td>
          <td>{{ $r->mov }}</td>
          <td>{{ $r->responsible_agency }}</td>
          <td>{{ $r->reporting_agency }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="13" class="small" style="text-align:center;">No data.</td>
        </tr>
      @endforelse
    </tbody>
  </table>
</body>
</html>
