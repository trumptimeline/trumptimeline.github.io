<?php

/**
 * Argument Config
 */

  $arguments = [
    'csv_import' => [
      'keys'    => ['c', 'csv-import'],
      'name'    => 'CSV Import',
      'help'    => '',
      'default' => ''
      ],
    'path_screenshots' => [
      'keys'    => ['S', 'path-screenshots'],
      'name'    => 'Screenshots Path',
      'help'    => '',
      'default' => 'screenshots'
      ],
    'path_posts' => [
      'keys'    => ['P', 'path-posts'],
      'name'    => 'Posts Path',
      'help'    => '',
      'default' => '_posts'
      ],
    'post_target' => [
      'keys'    => ['f', 'post-target'],
      'name'    => 'Post Target Filename',
      'help'    => '',
      'default' => ''
      ],
    'post_template' => [
      'keys'    => ['T', 'post-template'],
      'name'    => 'Post Template Path',
      'help'    => '',
      'default' => '_templates/default.md'
      ],
    'date' => [
      'keys'    => ['D', 'date'],
      'name'    => 'Post Date',
      'help'    => 'YYYY-mm-dd',
      'default' => ''
      ],
    'title' => [
      'keys'    => ['t', 'title'],
      'name'    => 'Post Title',
      'help'    => '',
      'default' => ''
      ],
    'description' => [
      'keys'    => ['d', 'description'],
      'name'    => 'Post Description',
      'help'    => '',
      'default' => ''
      ],
    'tags' => [
      'keys'    => ['k', 'tags'],
      'name'    => 'Post Tags',
      'help'    => '',
      'default' => ''
      ],
    'source_url' => [
      'keys'    => ['s', 'source-url'],
      'name'    => 'Source URL',
      'help'    => '',
      'default' => ''
      ],
    'source_name' => [
      'keys'    => ['n', 'source-name'],
      'name'    => 'Source Name',
      'help'    => '',
      'default' => ''
      ],
    'source_screenshot' => [
      'keys'    => ['i', 'source-screenshot'],
      'name'    => 'Source Screenshot',
      'help'    => '',
      'default' => ''
      ]
    ];

/**
 * Gather Configuration
 */

  $config = [];
  foreach ($arguments AS $argumentKey => $argumentCfg) {
    $config[$argumentKey] = getInput($argumentCfg);
  }

/**
 * CSV Processing
 */

  if ($config['csv_import']) {
    $header = null;
    if (($handle = fopen($config['csv_import'], 'r')) !== FALSE) {
      while (($data = fgetcsv($handle, 2048, ',')) !== FALSE) {
        if (!$header){
          $header = $data;
        }
        else {
          $line = array_combine($header, $data);
          generatePost(array_merge($config, $line));
        }
      }
      fclose($handle);
    }
  }

/**
 * Single Process
 */

  else {
    generatePost( $config );
  }

/**
 * Process Request
 */

  function generatePost( $config ){

    /**
     * Cleamup
     */

    if (@$config['tags'])
      $config['tags'] = array_map('trim', explode(',',preg_replace('/(^\[|\]$)/', '', $config['tags'])));

    /**
     * Prepare Target
     */

      if (empty($config['post_target']) && !empty($config['title'])) {
        $config['post_target'] = ($config['date'] ?: date('Y-m-d')) . '-' . preg_replace('/-+/', '-', preg_replace('/[^a-z0-9]/', '-', strtolower($config['title']))) . '.md';
      }
      if (!empty($config['post_target']) && file_exists($post_target)) {
        echo "Target File Exists " . $post_target . "\n";
        return false;
      }

    /**
     * Pull Screenshot / Page Data
     */

      if (empty($config['source_screenshot']) && !empty($config['source_url'])) {
        $exec_command = "phantomjs-screenshot.sh"
                      . " " . $config['source_url']
                      . " -p " . $config['path_screenshots']
                      . (@$config['post_target'] ? ' -f '.str_replace('.md', '.png', $config['post_target']): '');
        exec($exec_command, $screenshot_result);
        if ($screenshot_result) {
          $screenshot_data = array();
          foreach ($screenshot_result AS $line) {
            $line = explode(': ', $line);
            $screenshot_data[ $line[0] ] = $line[1];
          }
          $config['source_screenshot'] = substr(@$screenshot_data['Path'], strlen($config['path_screenshots'])+1);
          $config['title']             = $config['title'] ?: @$screenshot_data['Title'];
          $config['description']       = $config['description'] ?: @$screenshot_data['og:title'];
          $config['content']           = $config['content'] ?: @$screenshot_data['og:title']; // @$screenshot_data['og:description'];
          $config['date']              = $config['date'] ?: date('Y-m-d', strtotime((@$screenshot_data['article:published_time'] ?: @$screenshot_data['og:updated_time'])));
        }
        else {
          echo 'Source URL Error' . "\n";
          return false;
        }
      }

    /**
     * Prepare Target
     */

      if (empty($config['post_target']) && !empty($config['source_url'])) {
        $url = parse_url($config['source_url']);
        $config['post_target'] = ($config['date'] ?: date('Y-m-d')) . '-' . preg_replace('/^.*\/(.*?)$/', '$1', $url['path']) . '.md';
      }

    /**
     * Generate Post
     */

      $post_target = $config['path_posts'] . '/' . $config['post_target'];
      if (!file_exists($post_target)) {
        @mkdir(dirname($post_target), 0755, true);
        $template_content = file_get_contents($config['post_template']);
        if ($fo = fopen($post_target, 'w')) {
          foreach ($config AS $key => $value) {
            switch ($key) {
              default:
                if (is_array($value) || is_object($value))
                  $template_content = str_replace('{'.$key.'}', json_encode($value), $template_content);
                else
                  $template_content = str_replace('{'.$key.'}', $value, $template_content);
                break;
            }
          }
          $template_content = str_replace('{sources}', json_encode([[
                              'url'        => $config['source_url'],
                              'name'       => $config['source_name'],
                              'screenshot' => $config['source_screenshot'],
                              ]]), $template_content);
          $template_content = str_replace('{content}', '', $template_content);
          fwrite( $fo, $template_content );
          fclose( $fo );
          echo "Wrote post " . $post_target . "\n";
        }
        else {
          echo "Failed to open post " . $post_target . "\n";
          return false;
        }
      }
      else {
        echo "Target File Exists " . $post_target . "\n";
        return false;
      }

    /**
     * Done
     */

      return true;

  }

/**
 * [getInput description]
 * @param  [type] $argumentCfg [description]
 * @return [type]              [description]
 */

  function getInput( $argumentCfg ){
    global $argv;
    $keys    = (array)$argumentCfg['keys'];
    $help    = (string)$argumentCfg['help'];
    $default = (string)$argumentCfg['default'];
    $message = (string)$argumentCfg['name']
             . ($help ? ' - '.$help : '')
             . ($default ? ' ('.$default.')' : '');
    $prompt = true;
    foreach ($keys AS $key) {
      $key = strlen($key) == 1 ? '-' . $key : '--' . $key;
      for($i=0;$i<count($argv);$i++){
        if ($argv[$i] == '-q' || $argv[$i] == '--quiet'){
          $prompt = false;
        }
        if ($argv[$i] == $key){
          if (isset($argv[$i+1])) {
            echo "$message " . $argv[$i+1] . "\n";
            return $argv[$i+1];
          }
        }
      }
    }
    if (!$prompt) {
      return $default;
    }
    echo "$message: ";
    return trim(fgets(STDIN)) ?: $default;
  }
