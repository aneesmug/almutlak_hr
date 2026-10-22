<?php
// Report page shell, part 2 (approval chain, signatures, footer, scripts) - see includes/report_layout.php.
// Included at file scope by the report page after it sets $report_end.
$report_end = $report_end ?? [];
?>
                <?= report_card_close_html($report_end) ?>
            </div>
        </div>
        <footer class="footer no-print"><?= $site_footer ?? '' ?></footer>
    </div>
</div>
<?= report_scripts_html($report_end) ?>
</body>
</html>
