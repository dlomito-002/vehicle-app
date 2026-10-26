{{-- Tabla etiqueta → valor de la plantilla de correo. Recibe $rows (array). --}}
<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="border-collapse:collapse;border:1px solid #e4e7ec;border-radius:12px;">
    @foreach ($rows as $label => $value)
        <tr>
            <td style="padding:10px 14px;{{ $loop->last ? '' : 'border-bottom:1px solid #e4e7ec;' }}color:#667085;font-size:13px;width:44%;">{{ $label }}</td>
            <td style="padding:10px 14px;{{ $loop->last ? '' : 'border-bottom:1px solid #e4e7ec;' }}color:#101828;font-size:14px;font-weight:700;">{{ $value }}</td>
        </tr>
    @endforeach
</table>
