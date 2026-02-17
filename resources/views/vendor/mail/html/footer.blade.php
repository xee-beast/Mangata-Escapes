<tr>
  <td align="right" style="
      padding:0;
      margin:0;
  ">
    <table
      align="right"
      width="570"
      cellpadding="0"
      cellspacing="0"
      role="presentation"
      style="
        width:100%;
        max-width:570px;
        margin:0;
      "
    >
      <tr>
        <td
          align="right"
          style="
            padding:10px 60px 50px 30px;
            font-family:'Poppins', sans-serif !important;
            font-size:14px;
            line-height:1.5;
            color:#000000;
            text-align:right;
          "
        >
          {{ Illuminate\Mail\Markdown::parse($slot) }}
        </td>
      </tr>
    </table>
  </td>
</tr>
