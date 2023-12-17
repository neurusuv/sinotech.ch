<?php

global $wps_ic;

$user_credits = self::$user_credits;
$user_credits = self::$user_credits;
$stats_live = self::$stats_live;
$stats_local = self::$stats_local;
$stats_local_sum = self::$stats_local_sum;

/**
 * Quick fix for PHP undefined notices
 */
$wps_ic_active_settings['optimization']['lossless'] = '';
$wps_ic_active_settings['optimization']['intelligent'] = '';
$wps_ic_active_settings['optimization']['ultra'] = '';

/**
 * Decides which setting is active
 */
if (!empty($wps_ic::$settings['optimization'])) {
  if ($wps_ic::$settings['optimization'] == 'lossless') {
    $wps_ic_active_settings['optimization']['lossless'] = 'class="current"';
  } elseif ($wps_ic::$settings['optimization'] == 'intelligent') {
    $wps_ic_active_settings['optimization']['intelligent'] = 'class="current"';
  } else {
    $wps_ic_active_settings['optimization']['ultra'] = 'class="current"';
  }
} else {
  $wps_ic_active_settings['optimization']['intelligent'] = 'class="current"';
}


?>
<div class="wp-compress-settings">

    <div class="wp-compress-settings-row-blank">

        <div class="col-9 text-left white-box margin-right">
            <div class="pre-inner">
                <div class="inner inner-flex">
                  <?php
                  /**
                   * Is Live OFF?
                   */
                  if (empty($wps_ic::$settings['live-cdn']) || $wps_ic::$settings['live-cdn'] == '0') {
                  $donut_size = 1;
                  $savings = false;

                  if (isset ($stats_local_sum->bytes->compressed) && $stats_local_sum->bytes->compressed > 0 &&
                    isset ($stats_local_sum->bytes->original) && $stats_local_sum->bytes->original > 0) {
                    $savings = true;
                    $donut_size = 1 - ($stats_local_sum->bytes->compressed / $stats_local_sum->bytes->original);
                    $donut_size = number_format($donut_size, 1);
                    $user_savings = $donut_size * 100;
                  } else {
                    $savings = true;
                    $user_savings = 0;
                  }

                  if (empty($user_savings) || $user_savings == '') {
                    $user_savings = '0';
                  }

                  $user_savings = number_format($user_savings, 1);
                  ?>

                    <div class="left-side-box">
                        <div class="user-account-circle">
                            <div id="circle-big" data-value="<?php
                            echo $donut_size; ?>"></div>
                            <div class="dashboard-account-circle-text">
                              <?php
                              if ($savings) { ?>
                                  <h5><?php
                                    echo $user_savings . '%'; ?></h5>
                                  <h4>Savings</h4>
                                <?php
                              } ?>
                            </div>
                            <!-- -35s == 35% -->
                        </div>

                        <div class="youve-saved">
                          <?php
                          /**
                           * Live Stats Exist OR  Stats Local Exists
                           */
                          if (!empty($stats_live) || !empty($stats_local)) { ?>
                            <?php
                            if (empty($wps_ic::$settings['live-cdn']) || $wps_ic::$settings['live-cdn'] == '0') {

                              if (isset ($stats_local_sum->bytes->compressed) && $stats_local_sum->bytes->compressed > 0 && isset ($stats_local_sum->bytes->original) && $stats_local_sum->bytes->original > 0) {
                                echo '<h3>You\'ve Saved</h3>';

                                $savings = $stats_local_sum->bytes->original - $stats_local_sum->bytes->compressed;
                                $savings = wps_ic_size_format($savings, 0);
                                if ($savings <= 0) {
                                  echo '<h3 style="padding-right:20px;">No Savings Yet!</h3>';
                                } else {
                                  echo '<h4>' . $savings . '</h4>';
                                }
                              } else {
                                echo '<h3 style="padding-right:20px;">No Savings Yet!</h3>';
                              }

                            } else { ?>
                                <h3>You've Saved</h3>
                                <h4><?php
                                  echo $user_credits->formatted->bandwidth_savings_bytes; ?></h4>
                              <?php
                            } ?>
                              <div class="image-credits-remaining">
                                <?php
                                $local_requests_left = '';
                                $requests_left = '';
                                if (empty($wps_ic::$settings['live-cdn']) || $wps_ic::$settings['live-cdn'] == '0') {
                                  $requests_left = 'display:none;';
                                } else {
                                  $local_requests_left = 'display:none;';
                                }
                                ?>
                                  <a href="https://wpcompress.com/pricing" target="_blank"
                                     class="button button-primary local-requests-left" style="<?php
                                  echo $local_requests_left; ?>">
                                      <h5><?php
                                        echo self::$accountQuota['local']; ?></h5>
                                  </a>
                                  <a href="https://wpcompress.com/pricing" target="_blank"
                                     class="button button-primary requests-left" style="<?php
                                  echo $requests_left; ?>">
                                      <h5><?php echo self::$accountQuota['live']; ?></h5>
                                  </a>

                              </div>
                            <?php
                          } else {
                            ?>
                              <h3>No savings!</h3>
                              <a href="<?php echo admin_url('options-general.php?page=' . $wps_ic::$slug); ?>" class="button-primary button btn-start-optimization" style="margin-left: 10px;margin-right:10px;"><?php echo self::$accountQuota['local']; ?></a>
                            <?php
                          } ?>
                        </div>
                    </div>

                      <?php
                      if (empty($wps_ic::$settings['live-cdn']) || $wps_ic::$settings['live-cdn'] == '0') {
                      ?>
                        <div class="stats-boxes smaller">
                          <?php
                          } else { ?>
                            <div class="stats-boxes">
                              <?php
                              } ?>
                                <div class="stats-box-single">
                                    <div class="stats-box-icon-holder">
                                        <img src="<?php
                                        echo WPS_IC_URI; ?>/assets/images/icon-total-images.svg"/>
                                    </div>
                                    <div class="stats-box-text-holder">
                                      <?php
                                      if (empty($wps_ic::$settings['live-cdn']) || $wps_ic::$settings['live-cdn'] == '0') {
                                        $thumbs = get_intermediate_image_sizes();
                                        ?>
                                          <h3><?php
                                            if (isset ($stats_local_sum->bytes->requests) && $stats_local_sum->bytes->requests > 0) {
                                              echo $stats_local_sum->bytes->requests;
                                            } else {
                                                echo '0';
                                            }?></h3>
                                          <h5>Images</h5>
                                        <?php
                                      } else { ?>
                                          <h3><?php
                                            if (isset ($user_credits->formatted->cdn_requests)) {
                                              echo $user_credits->formatted->cdn_requests;
                                            } ?></h3>
                                          <h5>Total Images</h5>
                                        <?php
                                      } ?>
                                    </div>
                                </div>

                                <div class="stats-box-single">
                                    <div class="stats-box-icon-holder">
                                        <img src="<?php
                                        echo WPS_IC_URI; ?>/assets/images/icon-original-size.svg"/>
                                    </div>
                                    <div class="stats-box-text-holder">
                                      <?php
                                      if (empty($wps_ic::$settings['live-cdn']) || $wps_ic::$settings['live-cdn'] == '0') {
                                        ?>
                                          <h3>
                                            <?php
                                            if (empty($stats_local_sum->bytes->original) || $stats_local_sum->bytes->original == '') {
                                              echo '0';
                                            } else {
                                              echo $stats_local_sum->formatted->original;
                                            }
                                            ?>
                                          </h3>
                                          <h5>Original Size</h5>
                                        <?php
                                      } else { ?>
                                          <h3>
                                            <?php
                                            if (empty($user_credits->formatted->original_bandwidth) || $user_credits->formatted->original_bandwidth == '') {
                                              echo '0';
                                            } else {
                                              echo $user_credits->formatted->original_bandwidth;
                                            }
                                            ?></h3>
                                          <h5>Original</h5>
                                        <?php
                                      } ?>
                                    </div>
                                </div>

                                <div class="stats-box-single">
                                    <div class="stats-box-icon-holder">
                                        <img src="<?php
                                        echo WPS_IC_URI; ?>/assets/images/icon-after-optimization.svg"/>
                                    </div>
                                    <div class="stats-box-text-holder">
                                      <?php
                                      if (empty($wps_ic::$settings['live-cdn']) || $wps_ic::$settings['live-cdn'] == '0') {
                                        ?>
                                          <h3>
                                            <?php
                                            if (empty($stats_local_sum->bytes->compressed) || $stats_local_sum->bytes->compressed == '') {
                                              echo '0';
                                            } else {
                                              echo $stats_local_sum->formatted->compressed;
                                            }
                                            ?>
                                          </h3>
                                          <h5>Optimized</h5>
                                        <?php
                                      } else { ?>
                                          <h3>
                                            <?php
                                            if (empty($user_credits->formatted->cdn_bandwidth) || $user_credits->formatted->cdn_bandwidth == '') {
                                              echo '0';
                                            } else {
                                              echo $user_credits->formatted->cdn_bandwidth;
                                            }
                                            ?></h3>
                                          <h5>Optimized</h5>
                                        <?php
                                      } ?>
                                    </div>
                                </div>


                        </div>
                      <?php
                      }
                      else {
                        // Live is ON
                        if (!empty($stats_live)) {
                          if ($user_credits->bytes->bandwidth_savings > 0) {
                            $savings = true;
                            $donut_size = $user_credits->bytes->bandwidth_savings / 100;
                            $donut_size = number_format($donut_size, 1);
                            $donut_text = $user_savings = $user_credits->formatted->bandwidth_savings;
                          } else {
                            $savings = true;
                            $user_savings = 0;
                            $donut_text = '0';
                          }
                        } else {
                          $donut_size = 1;
                          $donut_text = 0;
                        }

                        $donut_text = number_format($donut_text, 1);
                        ?>

                          <div class="left-side-box">
                              <div class="user-account-circle">
                                  <div id="circle-big" data-value="<?php
                                  echo $donut_size; ?>"></div>
                                  <div class="dashboard-account-circle-text">
                                      <h5><?php
                                        echo $donut_text; ?>%</h5>
                                      <h4>Savings</h4>
                                  </div>
                                  <!-- -35s == 35% -->
                              </div>
                              <div class="youve-saved">
                                <?php
                                if (!empty($stats_live)) { ?>
                                    <h3>You've Saved</h3>
                                    <h4><?php echo $user_credits->formatted->bandwidth_savings_bytes; ?></h4>
                                  <?php
                                } else { ?>
                                    <h3>You've Saved</h3>
                                    <h4><?php echo 0 . ' MB'; ?></h4>
                                  <?php
                                } ?>
                                  <div class="image-credits-remaining">

                                    <?php
                                    if (empty($wps_ic::$settings['live-cdn']) || $wps_ic::$settings['live-cdn'] == '0') { ?>
                                        <a href="https://wpcompress.com/pricing" target="_blank"
                                           class="button button-primary local-requests-left">
                                            <h5><?php
                                              echo self::$accountQuota['local']; ?></h5>
                                        </a>
                                        <a href="https://wpcompress.com/pricing" target="_blank"
                                           class="button button-primary requests-left" style="display: none;">
                                            <h5><?php
                                              echo self::$accountQuota['live']; ?></h5>
                                        </a>
                                      <?php
                                    } else { ?>
                                        <a href="https://wpcompress.com/pricing" target="_blank"
                                           class="button button-primary local-requests-left" style="display: none;">
                                            <h5><?php
                                              echo self::$accountQuota['local']; ?></h5>
                                        </a>
                                        <a href="https://wpcompress.com/pricing" target="_blank"
                                           class="button button-primary requests-left">
                                            <h5><?php
                                              echo self::$accountQuota['live']; ?></h5>
                                        </a>
                                      <?php
                                    } ?>
                                  </div>
                              </div>
                          </div>

                          <div class="right-side-box">


                              <div class="stats-boxes">

                                  <div class="stats-box-single">
                                      <div class="stats-box-icon-holder">
                                          <img src="<?php echo WPS_IC_URI; ?>/assets/images/icon-original-size.svg"/>
                                      </div>
                                      <div class="stats-box-text-holder">
                                          <h5>Original</h5>
                                          <h3><?php echo $user_credits->formatted->original_bandwidth; ?></h3>
                                      </div>
                                  </div>

                                  <div class="stats-box-single">
                                      <div class="stats-box-icon-holder">
                                          <img src="<?php
                                          echo WPS_IC_URI; ?>/assets/images/icon-total-images.svg"/>
                                      </div>
                                      <div class="stats-box-text-holder">
                                          <h5>Optimized</h5>
                                          <h3><?php echo $user_credits->formatted->cdn_bandwidth; ?></h3>
                                      </div>
                                  </div>

                                  <div class="stats-box-single">
                                      <div class="stats-box-icon-holder">
                                          <img src="<?php echo WPS_IC_URI; ?>/assets/images/icon-after-optimization.svg"/>
                                      </div>
                                      <div class="stats-box-text-holder">
                                          <h5>Assets Served</h5>
                                          <h3><?php echo $user_credits->formatted->cdn_requests; ?></h3>
                                      </div>
                                  </div>


                              </div>
                          </div>
                        <?php
                      } ?>
                    </div>
                </div>
            </div>
        </div>


    </div>