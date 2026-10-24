<mjml>
  <mj-head>
    <mj-title>@yield('title', 'LaraMJML email')</mj-title>
  </mj-head>
  <mj-body background-color="#f4f4f4">
    <mj-section background-color="#ffffff" padding="24px">
      <mj-column>
        @yield('content')
      </mj-column>
    </mj-section>
  </mj-body>
</mjml>
