<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Yanrie Variety Store - POS</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <style>
        @media print {
            /* Hide the sidebar, application navigation header, tabs bar, and direct operational components */
            body *, 
            nav, 
            header, 
            aside, 
            .tabs-navigation-container {
                visibility: hidden !important;
            }
            
            /* Isolate and force only the low-stock container view component to remain layout readable */
            #low-stock-alert-section, 
            #low-stock-alert-section * {
                visibility: visible !important;
            }
            
            /* Position the container directly in the top-left of the printed document page */
            #low-stock-alert-section {
                position: absolute !important;
                left: 0 !important;
                top: 0 !important;
                width: 100% !important;
                background: white !important;
                color: black !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100 min-h-screen">

    {{ $slot }}

</body>
</html>