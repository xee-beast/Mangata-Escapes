<table
  align="center"
  width="100%"
  cellpadding="0"
  cellspacing="0"
  role="presentation"
  style="
    background-color: transparent;
    border: 2px dashed #E7E0DA;
    margin: 25px 0;
    padding: 24px;
    width: 100%;
    -premailer-cellpadding: 0;
    -premailer-cellspacing: 0;
    -premailer-width: 100%;
  "
>
  <tr>
    <td align="center" style="text-align: center;">
      {{ Illuminate\Mail\Markdown::parse($slot) }}
    </td>
  </tr>
</table>
