<?php
header("Content-Type: application/javascript; charset=UTF-8");
echo <<<JS
        window.alert = function() {};
        document.addEventListener('DOMContentLoaded', function() {
            document.body.addEventListener('click', function(event) {
                const link = event.target.closest('a');
                if (link && link.dataset.allowLink !== "true") event.preventDefault();
            });
        });
    JS;
