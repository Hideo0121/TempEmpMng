<!DOCTYPE html>
<html>
<head>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <title>{{ config('app.name') }}</title>
</head>
<body>
    <style>
        @media only screen and (max-width: 600px) {
            .inner-body,
            .footer {
                width: 100% !important;
            }
        }

        @media only screen and (max-width: 500px) {
            .button {
                width: 100% !important;
            }
        }
    </style>

    <table class="wrapper" width="100%" cellpadding="0" cellspacing="0" role="presentation">
        <tr>
            <td align="center">
                <table class="content" width="1200" cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; max-width: 1200px; margin: 0 auto;">
                    {{ $header ?? '' }}

                    <!-- Email Body -->
                    <tr>
                        <td class="body" width="100%" cellpadding="0" cellspacing="0" style="border-top: 1px solid #e8e5ef; border-bottom: 1px solid #e8e5ef; background-color: #edf2f7;">
                            <table class="inner-body" align="center" width="1160" cellpadding="0" cellspacing="0" role="presentation" style="width: 100%; max-width: 1160px; margin: 0 auto; background-color: #ffffff;">
                                <!-- Body content -->
                                <tr>
                                    <td class="content-cell" style="padding: 35px;">
                                        {!! Illuminate\Mail\Markdown::parse($slot) !!}

                                        {!! $subcopy ?? '' !!}
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td>
                            {{ $footer ?? '' }}
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
