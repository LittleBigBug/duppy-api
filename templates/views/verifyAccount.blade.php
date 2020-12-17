<!doctype html>

<html xmlns="http://www.w3.org/1999/xhtml">
    <head>
        <title>Verify your new account</title>

        <meta http-equiv="Content-Type" content="text/html;" charset="UTF-8" />
        <meta http-equiv="X-UA-Compatible" content=“IE=edge” />
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />

        <link rel="preconnect" href="https://fonts.gstatic.com">
        <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;700&display=swap" rel="stylesheet">

        <style>
            html,
            body {
                font-family: 'Montserrat', sans-serif;
                font-weight: 400;
                font-size: 16px;
                text-align: center;
                padding: 0;
                margin: 0;
            }

            .header {
                font-weight: 700;
                font-size: 32px;
                background-color: #2B2D33;
                padding: 32px;
                width: 100%;
            }

            .content,
            .disclaimer {
                background-color: #4F5259;
                width: 100%;
            }

            .code {
                background-color: #595C63;
                color: #649E75;
                padding: 8px;
                margin: 10px auto;
            }

            .disclaimer {
                color: #81848C;
                font-size: 10px;
            }
        </style>
    </head>

    <body>
        <div class="header">{{ $title }}</div>
        <div class="content">
            Your account is almost ready!<br>
            To activate it and log in, verify that you own this email by clicking
            <a href="{!! $url !!}"> here</a>
            <br/> <br/>

            Alternatively, you can copy and paste {!! $url !!} into your browser and enter this code:<br/>

            <div class="code">
                {{ $code }}
            </div>
        </div>
        <div class="disclaimer">
            If you didn't create an account using this address, you can safely ignore this email.<br/>
            This email was sent as a one-time mandatory sign up process.
        </div>
    </body>
</html>