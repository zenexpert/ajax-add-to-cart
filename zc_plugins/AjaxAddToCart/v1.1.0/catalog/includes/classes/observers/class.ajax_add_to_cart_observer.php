<?php
declare(strict_types=1);

if (!defined('IS_ADMIN_FLAG')) {
    die('Illegal Access');
}

class ajax_add_to_cart_observer extends base
{
    protected string $zcPluginDir;

    public function __construct()
    {
        if (!defined('AJAX_ADD_TO_CART_STATUS') || AJAX_ADD_TO_CART_STATUS !== 'true' || IS_ADMIN_FLAG === true) {
            return;
        }

        $this->zcPluginDir = str_replace(DIR_FS_CATALOG, '', dirname(__DIR__, 3) . '/');

        $this->attach($this, [
            'NOTIFY_HTML_HEAD_END',
            'NOTIFY_ZEN_REDIRECT'
        ]);
    }

    public function update(&$class, string $eventID, $p1 = null, $p2 = null, $p3 = null, $p4 = null): void
    {
        if ($eventID === 'NOTIFY_HTML_HEAD_END') {
            global $template, $current_page_base;

            $stylesheet = 'ajax_add_to_cart.css';
            $css_base_file = $this->getZcPluginDir() . DIR_WS_TEMPLATES . "default/css/$stylesheet";
            if (file_exists(DIR_FS_CATALOG . $css_base_file)) {
                echo '<link rel="stylesheet" href="' . $css_base_file . '">' . "\n";
            }

            if (isset($template) && is_object($template)) {
                $stylesheet_dir = $template->get_template_dir($stylesheet, DIR_WS_TEMPLATE, $current_page_base, 'css') . '/' . $stylesheet;
                if (!str_contains($stylesheet_dir, $this->getZcPluginDir()) && file_exists(DIR_FS_CATALOG . $stylesheet_dir)) {
                    echo '<link rel="stylesheet" href="' . $stylesheet_dir . '">' . "\n";
                }
            }

            $js_file = DIR_FS_CATALOG . $this->getZcPluginDir() . DIR_WS_TEMPLATES . 'default/jscript/ajax_add_to_cart_jscript.php';
            if (file_exists($js_file)) {
                require $js_file;
            }
        }

        if ($eventID === 'NOTIFY_ZEN_REDIRECT') {
            $isAjax = (!empty($_POST['ajax_add_to_cart']) || !empty($_GET['ajax_add_to_cart']));

            if ($isAjax) {
                global $currencies;

                $success = true;
                $messages = [];

                if (isset($_SESSION['messageToStack']) && is_array($_SESSION['messageToStack'])) {
                    foreach ($_SESSION['messageToStack'] as $key => $message) {
                        if (isset($message['type']) && in_array($message['type'], ['error', 'warning', 'caution'], true)) {
                            $success = false;
                        }
                        if (isset($message['text']) && $message['type'] !== 'success') {
                            $messages[] = strip_tags($message['text']);
                        }
                        unset($_SESSION['messageToStack'][$key]);
                    }
                    $_SESSION['messageToStack'] = array_values($_SESSION['messageToStack']);
                }

                if ($success) {
                    $added_products = [];

                    if (!empty($_POST['products_id']) && is_array($_POST['products_id'])) {
                        foreach ($_POST['products_id'] as $k => $v) {
                            // Multiple Products via Quantity Boxes (Key = Product ID, Value = Quantity)
                            if (is_numeric($k) && (int)$k > 0) {
                                if (is_numeric($v) && (float)$v > 0) {
                                    $added_products[(int)$k] = (int)$k;
                                }
                            }
                            // Multiple Products via Checkboxes (Key = sequential array index, Value = Product ID)
                            elseif (is_numeric($v) && (int)$v > 0) {
                                $qty = 1;
                                if (isset($_POST['cart_quantity']) && is_array($_POST['cart_quantity']) && isset($_POST['cart_quantity'][$v])) {
                                    $qty = (float)$_POST['cart_quantity'][$v];
                                }
                                if ($qty > 0) {
                                    $added_products[(int)$v] = (int)$v;
                                }
                            }
                        }
                    }
                    // Standard Single Product Forms
                    elseif (!empty($_POST['products_id'])) {
                        $added_products[(int)$_POST['products_id']] = (int)$_POST['products_id'];
                    }
                    // Direct "Buy Now" links
                    elseif (!empty($_GET['products_id'])) {
                        $added_products[(int)$_GET['products_id']] = (int)$_GET['products_id'];
                    }

                    $productNames = [];
                    foreach ($added_products as $pID) {
                        $name = zen_get_products_name($pID);
                        if (!empty($name)) {
                            $productNames[] = zen_output_string_protected($name);
                        }
                    }

                    if (!empty($productNames)) {
                        $productNamesList = '<b>' . implode('</b>, <b>', $productNames) . '</b>';
                        $messages[] = sprintf(AJAX_ADD_TO_CART_SUCCESS_ADDED, $productNamesList);
                    } else {
                        $messages[] = AJAX_ADD_TO_CART_SUCCESS_MESSAGE;
                    }
                }

                // Generate the exact native HTML for the Quantity in Cart text
                $productQtyHtml = '';
                if (isset($_SESSION['cart'])) {
                    $target_pID = 0;
                    if (!empty($_POST['products_id'])) {
                        if (is_array($_POST['products_id'])) {
                            reset($_POST['products_id']);
                            $target_pID = (int)key($_POST['products_id']);
                        } else {
                            $target_pID = (int)$_POST['products_id'];
                        }
                    } elseif (!empty($_GET['products_id'])) {
                        $target_pID = (int)$_GET['products_id'];
                    }

                    if ($target_pID > 0) {
                        // Check switch for showing QTY on this specific product
                        $show_qty = function_exists('zen_get_show_product_switch') ? zen_get_show_product_switch($target_pID, 'in_cart_qty') : 1;

                        if ($show_qty == 1) {
                            // Sums up all attributes natively
                            $qty = $_SESSION['cart']->in_cart_mixed($target_pID);

                            if ($qty > 0) {
                                $prefix = defined('PRODUCTS_ORDER_QTY_TEXT_IN_CART') ? PRODUCTS_ORDER_QTY_TEXT_IN_CART : 'Quantity in Cart: ';
                                // Wrap it in the exact markup native templates use, but add a class so we can reliably target it
                                $productQtyHtml = '<p class="cartInCart"><strong>' . $prefix . $qty . '</strong></p>';
                            }
                        }
                    }
                }

                $sideboxData = $this->renderShoppingCartSidebox();
                $headerCartHtml = '';
                if ($success && isset($_SESSION['cart']) && is_object($_SESSION['cart']) && $_SESSION['cart']->count_contents() > 0) {
                    $headerCartHtml = $this->renderHeaderCartLinks();
                }

                $response = [
                    'success' => $success,
                    'messages' => $messages,
                    'cartCount' => isset($_SESSION['cart']) ? $_SESSION['cart']->count_contents() : 0,
                    'cartTotal' => (isset($_SESSION['cart']) && is_object($currencies)) ? strip_tags($currencies->format($_SESSION['cart']->show_total())) : 0,
                    'sideboxHtml' => $sideboxData['html'],
                    'sideboxColumn' => $sideboxData['column'],
                    'columnBoxes' => $sideboxData['columnBoxes'],
                    'productQtyHtml' => $productQtyHtml,
                    'headerCartHtml' => $headerCartHtml,
                ];

                $p1 = null;
                $this->notify('NOTIFY_AJAX_ADD_TO_CART_FILTER_RESPONSE', $p1, $response);

                while (ob_get_level() > 0) {
                    if (!@ob_end_clean()) break;
                }

                header('Content-Type: application/json; charset=utf-8');
                echo json_encode($response, JSON_INVALID_UTF8_SUBSTITUTE);
                exit;
            }
        }
    }

    private function renderShoppingCartSidebox(): array
    {
        global $db, $template, $currencies, $cart, $current_page_base, $request_type, $PHP_SELF, $language_page_directory, $template_dir, $tplSetting, $tpl_settings, $zco_notifier, $messageStack, $detect;

        $sideboxHtml = '';
        $sideboxColumn = 'left';
        $columnBoxes = [];

        if (isset($template) && is_object($template)) {
            $sidebox_module = DIR_FS_CATALOG . DIR_WS_MODULES . zen_get_module_directory('sideboxes/shopping_cart.php');

            if (file_exists($sidebox_module)) {
                $column_box_default = 'tpl_box_default_left.php';
                $column_width = defined('BOX_WIDTH_LEFT') ? BOX_WIDTH_LEFT : '150px';

                $tDir = isset($template_dir) ? $template_dir : 'default';

                $layout_sql = "SELECT layout_box_location FROM " . TABLE_LAYOUT_BOXES . "
                               WHERE layout_box_name = 'shopping_cart.php' AND layout_template = '" . zen_db_input($tDir) . "' LIMIT 1";
                $layout_check = $db->Execute($layout_sql);

                if ($layout_check->EOF) {
                    $layout_sql = "SELECT layout_box_location FROM " . TABLE_LAYOUT_BOXES . "
                                   WHERE layout_box_name = 'shopping_cart.php' AND layout_template = 'default' LIMIT 1";
                    $layout_check = $db->Execute($layout_sql);
                    $tDir = 'default';
                }

                $my_location = 0;
                if (!$layout_check->EOF) {
                    $my_location = (int)$layout_check->fields['layout_box_location'];
                    if ($my_location === 1) {
                        $column_box_default = 'tpl_box_default_right.php';
                        $column_width = defined('BOX_WIDTH_RIGHT') ? BOX_WIDTH_RIGHT : '150px';
                        $sideboxColumn = 'right';
                    }
                }

                $all_boxes_sql = "SELECT layout_box_name FROM " . TABLE_LAYOUT_BOXES . "
                                  WHERE layout_template = '" . zen_db_input($tDir) . " '
                                  AND layout_box_location = " . $my_location . "
                                  AND layout_box_status = 1
                                  ORDER BY layout_box_sort_order ASC";
                $all_boxes = $db->Execute($all_boxes_sql);
                foreach ($all_boxes as $box) {
                    $columnBoxes[] = '#' . str_replace(['_', '.php'], '', $box['layout_box_name']);
                }

                $box_id = str_replace(['_', '.php'], '', 'shopping_cart.php');

                if (!isset($tplSetting) && class_exists('TemplateSettings')) {
                    $tplSetting = new TemplateSettings(isset($tpl_settings) && is_array($tpl_settings) ? $tpl_settings : []);
                }

                ob_start();
                try {
                    require($sidebox_module);
                } catch (\Throwable $e) {
                }
                $sideboxHtml = ob_get_clean();
            }
        }

        return [
            'html' => $sideboxHtml,
            'column' => $sideboxColumn,
            'columnBoxes' => $columnBoxes
        ];
    }

    /**
     * Render the active template's native Shopping Cart / Checkout header links
     * by extracting the cart-gated markup from tpl_header.php.
     */
    public function renderHeaderCartLinks(): string
    {
        if (defined('AJAX_ADD_TO_CART_INJECT_HEADER_LINKS') && AJAX_ADD_TO_CART_INJECT_HEADER_LINKS !== 'true') {
            return '';
        }

        global $template, $current_page_base, $detect, $currencies, $tplSetting, $request_type;

        $headerFile = $this->locateHeaderTemplateFile();
        if ($headerFile === '') {
            return '';
        }

        $source = @file_get_contents($headerFile);
        if ($source === false || $source === '') {
            return '';
        }

        $snippets = $this->extractHeaderCartSnippets($source);
        $snippet = $this->selectActiveHeaderSnippet($snippets);
        if ($snippet === '') {
            return '';
        }

        return $this->renderHeaderSnippet($snippet);
    }

    public function locateHeaderTemplateFile(): string
    {
        global $template, $current_page_base;

        if (isset($template) && is_object($template) && method_exists($template, 'get_template_dir')) {
            $headerDir = $template->get_template_dir('tpl_header.php', DIR_WS_TEMPLATE, $current_page_base, 'common');
            if (is_string($headerDir) && $headerDir !== '') {
                $headerFile = rtrim($headerDir, '/\\') . '/tpl_header.php';
                if (is_file($headerFile)) {
                    return $headerFile;
                }
            }
        }

        if (defined('DIR_FS_CATALOG') && defined('DIR_WS_TEMPLATE')) {
            $fallback = DIR_FS_CATALOG . DIR_WS_TEMPLATE . 'common/tpl_header.php';
            if (is_file($fallback)) {
                return $fallback;
            }
        }

        return '';
    }

    /**
     * @return list<array{source: string, layout: string}>
     */
    public function extractHeaderCartSnippets(string $source): array
    {
        $tokens = @token_get_all($source);
        if (!is_array($tokens) || $tokens === []) {
            return [];
        }

        $n = count($tokens);
        $depth = 0;
        $layoutAtDepth = [];
        $pendingLayout = null;
        $elseChainLayout = false;
        $snippets = [];
        $i = 0;

        while ($i < $n) {
            $tok = $tokens[$i];
            $name = is_array($tok) ? $tok[0] : $tok;
            $text = is_array($tok) ? $tok[1] : $tok;

            if ($name === T_IF || $name === T_ELSEIF) {
                $parsed = $this->parseIfConditionAndBody($tokens, $i);
                if ($parsed === null) {
                    $i++;
                    continue;
                }

                $cond = $parsed['condition'];
                $isLayoutMobile = (bool)preg_match('/isMobile\s*\(|layoutType\s*[\'"]?\s*==\s*[\'"]mobile[\'"]/i', $cond);
                $isLayoutTablet = (bool)preg_match('/isTablet\s*\(|layoutType\s*[\'"]?\s*==\s*[\'"]tablet[\'"]/i', $cond);
                $isCountContents = (bool)preg_match('/count_contents\s*\(/i', $cond);

                if ($isCountContents) {
                    if ($this->snippetLooksLikeCartOrCheckout($parsed['body']) || $this->snippetLooksLikeCartOrCheckout($parsed['full'])) {
                        $layout = 'unknown';
                        for ($d = $depth; $d >= 1; $d--) {
                            if (isset($layoutAtDepth[$d])) {
                                $layout = $layoutAtDepth[$d];
                                break;
                            }
                        }
                        $snippets[] = [
                            'source' => $parsed['full'],
                            'layout' => $layout,
                        ];
                    }
                    $i = $parsed['endIndex'] + 1;
                    continue;
                }

                if ($isLayoutMobile) {
                    $pendingLayout = 'mobile';
                    $elseChainLayout = true;
                } elseif ($isLayoutTablet) {
                    $pendingLayout = 'tablet';
                    $elseChainLayout = true;
                }

                $i = $parsed['bodyStartIndex'];
                continue;
            }

            if ($name === T_ELSE) {
                $j = $i + 1;
                while ($j < $n && $this->isIgnorableToken($tokens[$j])) {
                    $j++;
                }
                $nextName = ($j < $n && is_array($tokens[$j])) ? $tokens[$j][0] : (($j < $n) ? $tokens[$j] : null);
                $nextText = ($j < $n) ? (is_array($tokens[$j]) ? $tokens[$j][1] : $tokens[$j]) : '';
                if ($elseChainLayout && $nextName !== T_IF && $nextName !== T_ELSEIF && $nextText !== 'if') {
                    $pendingLayout = 'desktop';
                }
                $i++;
                continue;
            }

            if ($text === '{') {
                $depth++;
                if ($pendingLayout !== null) {
                    $layoutAtDepth[$depth] = $pendingLayout;
                    $pendingLayout = null;
                }
                $i++;
                continue;
            }

            if ($text === '}') {
                unset($layoutAtDepth[$depth]);
                $depth--;
                if ($depth < 0) {
                    $depth = 0;
                }
                if ($depth === 0 && $elseChainLayout) {
                    $k = $i + 1;
                    while ($k < $n && $this->isIgnorableToken($tokens[$k])) {
                        $k++;
                    }
                    $nextName = ($k < $n && is_array($tokens[$k])) ? $tokens[$k][0] : null;
                    if ($nextName !== T_ELSE && $nextName !== T_ELSEIF) {
                        $elseChainLayout = false;
                        $pendingLayout = null;
                    }
                }
                $i++;
                continue;
            }

            $i++;
        }

        return $snippets;
    }

    /**
     * @param list<array{source: string, layout: string}> $snippets
     */
    public function selectActiveHeaderSnippet(array $snippets, ?string $layout = null): string
    {
        if ($snippets === []) {
            return '';
        }
        if (count($snippets) === 1) {
            return $snippets[0]['source'];
        }

        $wanted = $layout ?? $this->currentHeaderLayout();

        foreach ($snippets as $snippet) {
            if ($snippet['layout'] === $wanted) {
                return $snippet['source'];
            }
        }

        foreach ($snippets as $snippet) {
            if ($snippet['layout'] === 'desktop') {
                return $snippet['source'];
            }
        }

        return $snippets[count($snippets) - 1]['source'];
    }

    public function renderHeaderSnippet(string $snippet): string
    {
        if (!isset($_SESSION['cart']) || !is_object($_SESSION['cart']) || (int)$_SESSION['cart']->count_contents() === 0) {
            return '';
        }

        $code = ltrim($snippet);
        if (!preg_match('/^<\?(?:php|=)/i', $code)) {
            $code = '<?php ' . $code;
        }

        $tokens = @token_get_all($code);
        if (!is_array($tokens) || $tokens === []) {
            return '';
        }

        $tokens = $this->unwrapCartCountIfTokens($tokens);
        return trim($this->renderWhitelistedTokens($tokens));
    }

    /**
     * Drop `if (count_contents…)` wrappers; the caller already verified the cart is non-empty.
     *
     * @param array<int, mixed> $tokens
     * @return array<int, mixed>
     */
    private function unwrapCartCountIfTokens(array $tokens): array
    {
        $out = [];
        $n = count($tokens);
        $i = 0;
        while ($i < $n) {
            $name = is_array($tokens[$i]) ? $tokens[$i][0] : $tokens[$i];
            if ($name === T_IF) {
                $parsed = $this->parseIfConditionAndBody($tokens, $i);
                if ($parsed !== null && preg_match('/count_contents\s*\(/i', $parsed['condition'])) {
                    $from = $parsed['bodyStartIndex'] + 1;
                    $to = $parsed['endIndex'] - 1;
                    $body = [];
                    if ($to >= $from) {
                        $body = array_slice($tokens, $from, $to - $from + 1);
                    }
                    foreach ($this->unwrapCartCountIfTokens($body) as $tok) {
                        $out[] = $tok;
                    }
                    $i = $parsed['endIndex'] + 1;
                    continue;
                }
            }
            $out[] = $tokens[$i];
            $i++;
        }
        return $out;
    }

    /**
     * Render mixed HTML/PHP using a whitelist: inline HTML plus echo/print/<?= of
     * constants, string/number literals, concatenations, and a small set of storefront helpers.
     * Unknown PHP is skipped, never executed.
     *
     * @param array<int, mixed> $tokens
     */
    private function renderWhitelistedTokens(array $tokens): string
    {
        $html = '';
        $n = count($tokens);
        $i = 0;
        while ($i < $n) {
            $name = is_array($tokens[$i]) ? $tokens[$i][0] : $tokens[$i];

            if ($name === T_INLINE_HTML) {
                $html .= $tokens[$i][1];
                $i++;
                continue;
            }

            if ($name === T_OPEN_TAG_WITH_ECHO) {
                $i++;
                $value = $this->evalAllowedExpression($tokens, $i, [T_CLOSE_TAG]);
                if ($value !== null) {
                    $html .= $this->stringifyRendered($value);
                }
                if ($i < $n && (is_array($tokens[$i]) ? $tokens[$i][0] : $tokens[$i]) === T_CLOSE_TAG) {
                    $i++;
                }
                continue;
            }

            if ($name === T_OPEN_TAG) {
                $i++;
                $html .= $this->renderPhpStatements($tokens, $i);
                continue;
            }

            $i++;
        }
        return $html;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function renderPhpStatements(array $tokens, int &$i): string
    {
        $html = '';
        $n = count($tokens);
        while ($i < $n) {
            $this->skipWhitespaceComments($tokens, $i);
            if ($i >= $n) {
                break;
            }
            $name = is_array($tokens[$i]) ? $tokens[$i][0] : $tokens[$i];
            $text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];

            if ($name === T_CLOSE_TAG) {
                $i++;
                break;
            }
            if ($name === T_INLINE_HTML) {
                break;
            }
            if ($text === ';') {
                $i++;
                continue;
            }
            if ($name === T_ECHO || $name === T_PRINT) {
                $i++;
                $value = $this->evalAllowedExpression($tokens, $i, [';', T_CLOSE_TAG]);
                if ($value !== null) {
                    $html .= $this->stringifyRendered($value);
                }
                if ($i < $n) {
                    $t = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                    if ($t === ';') {
                        $i++;
                    }
                }
                continue;
            }

            $this->skipStatement($tokens, $i);
        }
        return $html;
    }

    /**
     * @param array<int, mixed> $tokens
     * @param list<int|string> $terminators
     * @return mixed
     */
    private function evalAllowedExpression(array $tokens, int &$i, array $terminators)
    {
        $this->skipWhitespaceComments($tokens, $i);
        if ($i >= count($tokens) || $this->tokenIsTerminator($tokens[$i], $terminators)) {
            return null;
        }

        $left = $this->evalAllowedPrimary($tokens, $i);
        if ($left === null) {
            $this->skipUntilTerminators($tokens, $i, $terminators);
            return null;
        }

        while ($i < count($tokens)) {
            $this->skipWhitespaceComments($tokens, $i);
            if ($i >= count($tokens) || $this->tokenIsTerminator($tokens[$i], $terminators)) {
                break;
            }
            $text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            if ($text === '.') {
                $i++;
                $right = $this->evalAllowedPrimary($tokens, $i);
                if ($right === null) {
                    $this->skipUntilTerminators($tokens, $i, $terminators);
                    return null;
                }
                $left = $this->stringifyRendered($left) . $this->stringifyRendered($right);
                continue;
            }
            if ($text === ',') {
                break;
            }
            $this->skipUntilTerminators($tokens, $i, $terminators);
            return null;
        }

        return $left;
    }

    /**
     * @param array<int, mixed> $tokens
     * @return mixed
     */
    private function evalAllowedPrimary(array $tokens, int &$i)
    {
        $this->skipWhitespaceComments($tokens, $i);
        if ($i >= count($tokens)) {
            return null;
        }

        $tok = $tokens[$i];
        $name = is_array($tok) ? $tok[0] : $tok;
        $text = is_array($tok) ? $tok[1] : $tok;

        if ($name === T_LNUMBER) {
            $i++;
            return (int)$text;
        }
        if ($name === T_DNUMBER) {
            $i++;
            return (float)$text;
        }
        if ($name === T_CONSTANT_ENCAPSED_STRING) {
            $i++;
            return $this->unquotePhpString($text);
        }
        if ($text === '(') {
            $i++;
            $inner = $this->evalAllowedExpression($tokens, $i, [')']);
            if ($i < count($tokens) && ((is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i]) === ')')) {
                $i++;
            }
            return $inner;
        }
        if ($name === T_STRING) {
            $id = $text;
            $i++;
            $this->skipWhitespaceComments($tokens, $i);
            if ($i < count($tokens) && ((is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i]) === '(')) {
                return $this->evalAllowedFunction($id, $tokens, $i);
            }
            $lower = strtolower($id);
            if ($lower === 'true') {
                return true;
            }
            if ($lower === 'false') {
                return false;
            }
            if ($lower === 'null') {
                return null;
            }
            if (defined($id)) {
                return constant($id);
            }
            return null;
        }
        if ($name === T_VARIABLE) {
            return $this->evalAllowedVariable($tokens, $i);
        }

        return null;
    }

    /**
     * @param array<int, mixed> $tokens
     * @return mixed
     */
    private function evalAllowedFunction(string $id, array $tokens, int &$i)
    {
        $allowed = [
            'zen_href_link' => true,
            'htmlspecialchars' => true,
            'htmlentities' => true,
            'zen_output_string' => true,
            'zen_output_string_protected' => true,
        ];

        $i++;
        $args = [];
        $this->skipWhitespaceComments($tokens, $i);
        if ($i < count($tokens) && ((is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i]) !== ')')) {
            while ($i < count($tokens)) {
                $arg = $this->evalAllowedExpression($tokens, $i, [',', ')']);
                $args[] = $arg;
                $this->skipWhitespaceComments($tokens, $i);
                if ($i >= count($tokens)) {
                    break;
                }
                $text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                if ($text === ',') {
                    $i++;
                    continue;
                }
                if ($text === ')') {
                    break;
                }
                $this->skipUntilTerminators($tokens, $i, [')']);
                break;
            }
        }
        if ($i < count($tokens) && ((is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i]) === ')')) {
            $i++;
        }

        if (!isset($allowed[$id]) || !function_exists($id)) {
            return null;
        }

        try {
            return $id(...$args);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * @param array<int, mixed> $tokens
     * @return mixed
     */
    private function evalAllowedVariable(array $tokens, int &$i)
    {
        $var = is_array($tokens[$i]) ? $tokens[$i][1] : '';
        $i++;
        $this->skipWhitespaceComments($tokens, $i);

        if ($var === '$_SESSION') {
            if (!$this->consumeText($tokens, $i, '[')) {
                return null;
            }
            $this->skipWhitespaceComments($tokens, $i);
            if ($i >= count($tokens) || (is_array($tokens[$i]) ? $tokens[$i][0] : null) !== T_CONSTANT_ENCAPSED_STRING) {
                return null;
            }
            $key = $this->unquotePhpString($tokens[$i][1]);
            $i++;
            if ($key !== 'cart' || !$this->consumeText($tokens, $i, ']')) {
                return null;
            }
            if (!$this->consumeObjectOperator($tokens, $i)) {
                return null;
            }
            $this->skipWhitespaceComments($tokens, $i);
            if ($i >= count($tokens) || (is_array($tokens[$i]) ? $tokens[$i][0] : null) !== T_STRING) {
                return null;
            }
            $method = $tokens[$i][1];
            $i++;
            if (!in_array($method, ['count_contents', 'show_total'], true)) {
                return null;
            }
            if (!$this->consumeText($tokens, $i, '(') || !$this->consumeText($tokens, $i, ')')) {
                return null;
            }
            if (!isset($_SESSION['cart']) || !is_object($_SESSION['cart']) || !method_exists($_SESSION['cart'], $method)) {
                return null;
            }
            try {
                return $_SESSION['cart']->{$method}();
            } catch (\Throwable $e) {
                return null;
            }
        }

        if ($var === '$currencies') {
            global $currencies;
            if (!$this->consumeObjectOperator($tokens, $i)) {
                return null;
            }
            $this->skipWhitespaceComments($tokens, $i);
            if ($i >= count($tokens) || (is_array($tokens[$i]) ? $tokens[$i][0] : null) !== T_STRING) {
                return null;
            }
            $method = $tokens[$i][1];
            $i++;
            if ($method !== 'format' || !$this->consumeText($tokens, $i, '(')) {
                return null;
            }
            $arg = $this->evalAllowedExpression($tokens, $i, [')']);
            if (!$this->consumeText($tokens, $i, ')')) {
                return null;
            }
            if (!isset($currencies) || !is_object($currencies) || !method_exists($currencies, 'format')) {
                return null;
            }
            try {
                return $currencies->format($arg);
            } catch (\Throwable $e) {
                return null;
            }
        }

        if ($var === '$tplSetting') {
            global $tplSetting;
            if (!$this->consumeObjectOperator($tokens, $i)) {
                return null;
            }
            $this->skipWhitespaceComments($tokens, $i);
            if ($i >= count($tokens) || (is_array($tokens[$i]) ? $tokens[$i][0] : null) !== T_STRING) {
                return null;
            }
            $prop = $tokens[$i][1];
            $i++;
            if (!isset($tplSetting) || !is_object($tplSetting)) {
                return null;
            }
            try {
                return $tplSetting->{$prop};
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function consumeText(array $tokens, int &$i, string $wanted): bool
    {
        $this->skipWhitespaceComments($tokens, $i);
        if ($i >= count($tokens)) {
            return false;
        }
        $text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
        if ($text !== $wanted) {
            return false;
        }
        $i++;
        return true;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function consumeObjectOperator(array $tokens, int &$i): bool
    {
        $this->skipWhitespaceComments($tokens, $i);
        if ($i >= count($tokens)) {
            return false;
        }
        $name = is_array($tokens[$i]) ? $tokens[$i][0] : $tokens[$i];
        $text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
        if ($name !== T_OBJECT_OPERATOR && $text !== '->') {
            return false;
        }
        $i++;
        return true;
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function skipWhitespaceComments(array $tokens, int &$i): void
    {
        $n = count($tokens);
        while ($i < $n && is_array($tokens[$i]) && in_array($tokens[$i][0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT], true)) {
            $i++;
        }
    }

    /**
     * @param array<int, mixed> $tokens
     * @param list<int|string> $terminators
     */
    private function skipUntilTerminators(array $tokens, int &$i, array $terminators): void
    {
        $n = count($tokens);
        $depth = 0;
        while ($i < $n) {
            if ($depth === 0 && $this->tokenIsTerminator($tokens[$i], $terminators)) {
                return;
            }
            $text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            $name = is_array($tokens[$i]) ? $tokens[$i][0] : $tokens[$i];
            if ($name === T_CLOSE_TAG && $depth === 0) {
                return;
            }
            if ($text === '(') {
                $depth++;
            } elseif ($text === ')' && $depth > 0) {
                $depth--;
            }
            $i++;
        }
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function skipStatement(array $tokens, int &$i): void
    {
        $n = count($tokens);
        $paren = 0;
        $brace = 0;
        while ($i < $n) {
            $name = is_array($tokens[$i]) ? $tokens[$i][0] : $tokens[$i];
            $text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            if ($name === T_CLOSE_TAG && $paren === 0 && $brace === 0) {
                return;
            }
            if ($text === '(') {
                $paren++;
            } elseif ($text === ')' && $paren > 0) {
                $paren--;
            } elseif ($text === '{') {
                $brace++;
            } elseif ($text === '}' && $brace > 0) {
                $brace--;
                $i++;
                if ($brace === 0 && $paren === 0) {
                    return;
                }
                continue;
            } elseif ($text === ';' && $paren === 0 && $brace === 0) {
                $i++;
                return;
            }
            $i++;
        }
    }

    /**
     * @param mixed $token
     * @param list<int|string> $terminators
     */
    private function tokenIsTerminator($token, array $terminators): bool
    {
        $name = is_array($token) ? $token[0] : $token;
        $text = is_array($token) ? $token[1] : $token;
        foreach ($terminators as $terminator) {
            if ($terminator === $name || $terminator === $text) {
                return true;
            }
        }
        return false;
    }

    private function unquotePhpString(string $text): string
    {
        if ($text === '' || ($text[0] !== "'" && $text[0] !== '"')) {
            return $text;
        }
        $inner = substr($text, 1, -1);
        if ($text[0] === "'") {
            return str_replace(["\\\\", "\\'"], ["\\", "'"], $inner);
        }
        return stripcslashes($inner);
    }

    /**
     * @param mixed $value
     */
    private function stringifyRendered($value): string
    {
        if ($value === null || is_bool($value)) {
            return $value ? '1' : '';
        }
        if (is_scalar($value)) {
            return (string)$value;
        }
        return '';
    }

    public function currentHeaderLayout(): string
    {
        global $detect;

        $layoutType = $_SESSION['layoutType'] ?? '';
        if ($layoutType === 'mobile') {
            return 'mobile';
        }
        if ($layoutType === 'tablet') {
            return 'tablet';
        }
        if ($layoutType === 'full' || $layoutType === 'desktop') {
            return 'desktop';
        }

        if (isset($detect) && is_object($detect)) {
            $isMobile = method_exists($detect, 'isMobile') ? (bool)$detect->isMobile() : false;
            $isTablet = method_exists($detect, 'isTablet') ? (bool)$detect->isTablet() : false;
            if ($isMobile && !$isTablet) {
                return 'mobile';
            }
            if ($isTablet) {
                return 'tablet';
            }
        }

        return 'desktop';
    }

    /**
     * @param array<int, mixed> $tokens
     * @return array{condition: string, body: string, full: string, bodyStartIndex: int, endIndex: int}|null
     */
    private function parseIfConditionAndBody(array $tokens, int $ifIndex): ?array
    {
        $n = count($tokens);
        $i = $ifIndex + 1;

        while ($i < $n && $this->isIgnorableToken($tokens[$i])) {
            $i++;
        }
        if ($i >= $n) {
            return null;
        }

        $open = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
        if ($open !== '(') {
            return null;
        }

        $paren = 0;
        $condition = '';
        for (; $i < $n; $i++) {
            $text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
            $condition .= $text;
            if ($text === '(') {
                $paren++;
            } elseif ($text === ')') {
                $paren--;
                if ($paren === 0) {
                    $i++;
                    break;
                }
            }
        }

        while ($i < $n && $this->isIgnorableToken($tokens[$i])) {
            $i++;
        }
        if ($i >= $n) {
            return null;
        }

        $openText = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
        $alt = ($openText === ':');
        if ($openText !== '{' && !$alt) {
            return null;
        }

        $bodyStartIndex = $i;
        $i++;
        $bodyContentStart = $i;

        if ($alt) {
            $ifDepth = 1;
            for (; $i < $n; $i++) {
                $name = is_array($tokens[$i]) ? $tokens[$i][0] : null;
                if ($name === T_IF) {
                    $ifDepth++;
                } elseif ($name === T_ENDIF) {
                    $ifDepth--;
                    if ($ifDepth === 0) {
                        break;
                    }
                }
            }
            $endIndex = $i;
        } else {
            $depth = 1;
            for (; $i < $n; $i++) {
                $name = is_array($tokens[$i]) ? $tokens[$i][0] : $tokens[$i];
                $text = is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
                if ($name === T_CURLY_OPEN || $name === T_DOLLAR_OPEN_CURLY_BRACES || $text === '{') {
                    $depth++;
                } elseif ($text === '}') {
                    $depth--;
                    if ($depth === 0) {
                        break;
                    }
                }
            }
            $endIndex = $i;
        }

        if ($i >= $n) {
            return null;
        }

        $bodyEnd = $endIndex > $bodyContentStart ? $endIndex - 1 : $bodyContentStart;
        $body = $this->tokensToString($tokens, $bodyContentStart, $bodyEnd);
        $full = $this->tokensToString($tokens, $ifIndex, $endIndex);

        return [
            'condition' => $condition,
            'body' => $body,
            'full' => $full,
            'bodyStartIndex' => $bodyStartIndex,
            'endIndex' => $endIndex,
        ];
    }

    private function snippetLooksLikeCartOrCheckout(string $code): bool
    {
        return (bool)preg_match(
            '/FILENAME_SHOPPING_CART|FILENAME_CHECKOUT(?:_SHIPPING|_ONE)?|HEADER_TITLE_CART_CONTENTS|HEADER_TITLE_CHECKOUT|shopping_cart|checkout_shipping|checkout_one|navCartContentsIndicator/i',
            $code
        );
    }

    /**
     * @param array<int, mixed> $tokens
     */
    private function tokensToString(array $tokens, int $start, int $end): string
    {
        $out = '';
        $n = count($tokens);
        if ($start < 0) {
            $start = 0;
        }
        if ($end >= $n) {
            $end = $n - 1;
        }
        for ($i = $start; $i <= $end; $i++) {
            $out .= is_array($tokens[$i]) ? $tokens[$i][1] : $tokens[$i];
        }
        return $out;
    }

    /**
     * @param mixed $token
     */
    private function isIgnorableToken($token): bool
    {
        if (!is_array($token)) {
            return false;
        }
        return in_array($token[0], [T_WHITESPACE, T_COMMENT, T_DOC_COMMENT, T_OPEN_TAG, T_CLOSE_TAG], true);
    }

    public function getZcPluginDir(): string
    {
        return $this->zcPluginDir;
    }
}
