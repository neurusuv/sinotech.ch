<div class="wp-compress-pre-wrapper-v4">
    <div class="wp-compress-pre-subheader">
        <div class="col-6">
            <ul>
                <li>
                    <h3>Compression Report</h3>
                    <?php
                    if (empty(self::$settings['live-cdn']) || self::$settings['live-cdn'] == '0') {
                        // Local Stats

                        if (empty(self::$stats_local)) {
                            echo '<li><span class="button-sample-data ic-tooltip" title="Chart will update when usage data is available.">Sample Data</span></li>';
                        } else {
                            echo '<li><span class="button-sample-data ic-tooltip" title="Chart data for Local Compression">Local</span></li>';
                        }
                    } else {
                        // Live Stats
                        if (empty(self::$stats_live)) {
                            echo '<li><span class="button-sample-data ic-tooltip" title="Chart will update when usage data is available.">Sample Data</span></li>';
                        } else {
                            echo '<li><span class="button-sample-data ic-tooltip" title="Chart data for Live Compression">Live</span></li>';
                        }
                    }
                    ?>
                </li>
            </ul>
        </div>
        <div class="col-6 last">
            <ul>
                <li><span class="legend-original"></span>Original Size</li>
                <li><span class="legend-after"></span>After Optimization</li>
            </ul>
        </div>
    </div>

    <div class="wp-compress-chart" style="height: 400px;">
     <canvas id="canvas"></canvas>
    </div>

</div>