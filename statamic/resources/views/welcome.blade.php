<!doctype html>
<html lang="en">
	<head>
        <meta charset="utf-8">
        <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
        <meta name="viewport" content="width=device-width">
        <title>New Statamic Site</title>
        <style>
            .container {
                display: flex;
                align-items: center;
                justify-content: center;
                height: 100vh;
                width: 100%;
            }
            h1 {
                color: #c3cdd6;
                font-family: "Lato", "Helvetica", "Arial", sans-serif;
                font-weight: 300;
                letter-spacing: 10px;
                text-transform: uppercase;
            }
            svg path { fill: #c3cdd6; }
        </style>
	</head>

	<body>
		<div class="container">
            <div class="content">
                {!! svg('statamic-mark') !!}
                <h1>Statamic 2</h1>
            </div>
		</div>
	</body>
</html>
