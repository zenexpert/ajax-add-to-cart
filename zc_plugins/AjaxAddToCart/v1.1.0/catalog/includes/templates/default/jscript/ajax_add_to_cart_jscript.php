<?php
/**
 * Loaded explicitly via the ajax_add_to_cart_observer NOTIFY_HTML_HEAD_END hook
 */
?>
<script>
    if (typeof jQuery !== 'undefined') {
        (function($) {
            $(function() {
                var formSelector = "<?= addslashes(AJAX_ADD_TO_CART_FORM_SELECTOR) ?>";
                var btnSelector = "<?= addslashes(AJAX_ADD_TO_CART_BUTTON_SELECTOR) ?>";
                var linkSelector = "<?= addslashes(AJAX_ADD_TO_CART_LINK_SELECTOR) ?>";
                var countSelector = "<?= addslashes(AJAX_ADD_TO_CART_CART_COUNT_SELECTOR) ?>";
                var totalSelector = "<?= addslashes(AJAX_ADD_TO_CART_CART_TOTAL_SELECTOR) ?>";
                var sideboxSelector = "<?= defined('AJAX_ADD_TO_CART_SIDEBOX_SELECTOR') ? addslashes(AJAX_ADD_TO_CART_SIDEBOX_SELECTOR) : '#shopping-cart, #shoppingcart' ?>";
                var inCartSelector = "<?= defined('AJAX_ADD_TO_CART_IN_CART_SELECTOR') ? addslashes(AJAX_ADD_TO_CART_IN_CART_SELECTOR) : '.cartInCart' ?>";
                var inCartParent = "<?= defined('AJAX_ADD_TO_CART_IN_CART_PARENT') ? addslashes(AJAX_ADD_TO_CART_IN_CART_PARENT) : '#cartAdd' ?>";
                var headerNavSelector = <?= json_encode(defined('AJAX_ADD_TO_CART_HEADER_NAV_SELECTOR') ? AJAX_ADD_TO_CART_HEADER_NAV_SELECTOR : '#navMain ul, ul.navbar-nav') ?>;
                var headerCartSelector = <?= json_encode(defined('AJAX_ADD_TO_CART_HEADER_CART_SELECTOR') ? AJAX_ADD_TO_CART_HEADER_CART_SELECTOR : 'a[href*="shopping_cart"], a.navCartContentsIndicator') ?>;
                var defaultSuccessMsg = "<?= addslashes(AJAX_ADD_TO_CART_SUCCESS_MESSAGE) ?>";

                if ($('#ajaxAddToCartModal').length === 0) {
                    $('body').append(
                        '<div id="ajaxAddToCartModal" class="aatc-modal" style="display:none;">' +
                        '<div class="aatc-modal-content">' +
                        '<div id="aatc-icon" class="aatc-icon"></div>' +
                        '<h3 id="aatc-msg" class="aatc-msg"></h3>' +
                        '<div id="aatc-totals" class="aatc-totals">' +
                        '<p class="aatc-totals-row"><?= JSCRIPT_AJAX_CART_QTY_IN_CART ?> <strong id="aatc-count" class="aatc-totals-val"></strong></p>' +
                        '<p class="aatc-totals-row"><?= JSCRIPT_AJAX_CART_CART_SUBTOTAL ?> <strong id="aatc-total" class="aatc-totals-val"></strong></p>' +
                        '</div>' +
                        '<div class="aatc-actions">' +
                        '<button id="aatc-close" class="aatc-btn aatc-btn-secondary"><?= JSCRIPT_AJAX_CART_CONTINUE_SHOPPING ?></button>' +
                        '<a href="<?= zen_href_link(FILENAME_SHOPPING_CART) ?>" id="aatc-checkout" class="aatc-btn aatc-btn-primary"><?= JSCRIPT_AJAX_CART_VIEW_CART ?></a>' +
                        '</div>' +
                        '</div>' +
                        '</div>'
                    );

                    $('#aatc-close, #ajaxAddToCartModal').on('click', function(e) {
                        if (e.target === this) {
                            $('#ajaxAddToCartModal').fadeOut('fast');
                        }
                    });
                }

                function injectHeaderCartLinks(response) {
                    if (!response.success || !response.headerCartHtml) {
                        return;
                    }

                    var $headerScope = $('#headerWrapper, #navMainWrapper, #navMain, header, nav.navbar, #header');
                    if (!$headerScope.length) {
                        $headerScope = $(document);
                    }

                    if (headerCartSelector && $headerScope.find(headerCartSelector).length) {
                        return;
                    }

                    var $nav = $();
                    if (headerNavSelector) {
                        $nav = $(headerNavSelector).filter(':visible').first();
                        if (!$nav.length) {
                            $nav = $(headerNavSelector).first();
                        }
                    }
                    if (!$nav.length) {
                        return;
                    }

                    $nav.children('li.last').removeClass('last');

                    var $insertAfter = $();
                    $nav.children('li').each(function() {
                        var $li = $(this);
                        if ($li.is('#nav-home') || $li.find('#nav-home, a[href*="logoff"], a[href*="log_off"], a[href*="account"], a[href*="login"], a[href*="log_in"]').length) {
                            $insertAfter = $li;
                        }
                    });

                    if ($insertAfter.length) {
                        $insertAfter.after(response.headerCartHtml);
                    } else {
                        $nav.append(response.headerCartHtml);
                    }
                }

                function handleAjaxResponse(response, $form) {
                    // Broadcast the response so any custom template add-on can intercept it
                    $(document).trigger('ajaxAddToCartSuccess', [response, $form]);

                    if (response.success) {
                        // Update loose mini-cart elements
                        if (countSelector !== '' && response.cartCount !== undefined) {
                            $(countSelector).html(response.cartCount);
                        }
                        if (totalSelector !== '' && response.cartTotal !== undefined) {
                            $(totalSelector).html(response.cartTotal);
                        }

                        // Update the entire Native Zen Cart Shopping Cart Sidebox
                        if (response.sideboxHtml && sideboxSelector !== '') {
                            var $sidebox = $(sideboxSelector);
                            if ($sidebox.length) {
                                $sidebox.replaceWith(response.sideboxHtml);
                            } else {
                                var $leftCol = $('#navColumnOne, #column-left');
                                var $rightCol = $('#navColumnTwo, #column-right');
                                var inserted = false;

                                if (response.columnBoxes && response.columnBoxes.length > 0) {
                                    var myIndex = response.columnBoxes.indexOf('#shoppingcart');
                                    if (myIndex !== -1) {
                                        for (var i = myIndex + 1; i < response.columnBoxes.length; i++) {
                                            var $nextBox = $(response.columnBoxes[i]);
                                            if ($nextBox.length) {
                                                $nextBox.before(response.sideboxHtml);
                                                inserted = true;
                                                break;
                                            }
                                        }
                                        if (!inserted) {
                                            for (var j = myIndex - 1; j >= 0; j--) {
                                                var $prevBox = $(response.columnBoxes[j]);
                                                if ($prevBox.length) {
                                                    $prevBox.after(response.sideboxHtml);
                                                    inserted = true;
                                                    break;
                                                }
                                            }
                                        }
                                    }
                                }

                                if (!inserted) {
                                    if (response.sideboxColumn === 'right' && $rightCol.length) {
                                        $rightCol.prepend(response.sideboxHtml);
                                    } else if (response.sideboxColumn === 'left' && $leftCol.length) {
                                        $leftCol.prepend(response.sideboxHtml);
                                    }
                                }

                                if (response.sideboxColumn === 'right' && $rightCol.length) {
                                    $rightCol.show();
                                } else if (response.sideboxColumn === 'left' && $leftCol.length) {
                                    $leftCol.show();
                                }
                            }
                        }

                        // Inject native header Shopping Cart / Checkout links when the
                        // empty-cart template omitted them. After a full page reload the
                        // template renders them itself and this becomes a no-op.
                        injectHeaderCartLinks(response);

                        // Update the Product Info "Quantity In Cart" text natively
                        if (response.productQtyHtml && inCartSelector !== '') {
                            var $inCartElem = $(inCartSelector);
                            if ($inCartElem.length) {
                                // The line is already there, perfectly replace it
                                $inCartElem.replaceWith(response.productQtyHtml);
                            } else {
                                // The line isn't there, inject it gracefully
                                var $parent = $(inCartParent);
                                if ($parent.length) {
                                    // Zen Cart naturally outputs it at the top of #cartAdd
                                    $parent.prepend(response.productQtyHtml);
                                } else if ($form && $form.length) {
                                    // Failsafe for custom templates: prepend it to the form
                                    $form.prepend(response.productQtyHtml);
                                }
                            }
                        }
                    }

                    // Update and trigger the Modal UI
                    if (response.success) {
                        var successMsg = response.messages.length > 0 ? response.messages.join('<br>') : defaultSuccessMsg;

                        $('#aatc-icon').html('&#10004;').removeClass('error').addClass('success');
                        $('#aatc-msg').html(successMsg);
                        $('#aatc-count').text(response.cartCount);
                        $('#aatc-total').text(response.cartTotal);
                        $('#aatc-totals').show();
                    } else {
                        var errorMsg = response.messages.length > 0 ? response.messages.join('<br>') : '<?= JSCRIPT_AJAX_CART_ERROR_REQUEST ?>';

                        $('#aatc-icon').html('&#10006;').removeClass('success').addClass('error');
                        $('#aatc-msg').html('<strong><?= JSCRIPT_AJAX_CART_ERROR_REQUEST_NOTICE ?></strong><br>' + errorMsg);
                        $('#aatc-totals').hide();
                    }

                    // Suppress the modal if a custom template add-on overrides it
                    if (!response.disableModal) {
                        $('#ajaxAddToCartModal').fadeIn('fast');
                    }
                }

                if (formSelector !== '') {
                    $(document).off('submit.ajaxAddToCart').on('submit.ajaxAddToCart', formSelector, function(e) {
                        var $form = $(this);

                        var hasFile = false;
                        $form.find('input[type="file"]').each(function() {
                            if ($(this).val() !== '') hasFile = true;
                        });
                        if (hasFile) return;

                        e.preventDefault();

                        var formData = new FormData($form[0]);
                        formData.append('ajax_add_to_cart', '1');

                        var $submitBtn = $form.find(btnSelector);
                        $submitBtn.prop('disabled', true).css('opacity', '0.5');

                        $.ajax({
                            url: $form.attr('action') || window.location.href,
                            type: $form.attr('method') || 'POST',
                            data: formData,
                            processData: false,
                            contentType: false,
                            dataType: 'json',
                            success: function(response) {
                                $submitBtn.prop('disabled', false).css('opacity', '1');
                                handleAjaxResponse(response, $form);
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                console.error("Ajax Add To Cart failed. Falling back to native submit.", textStatus, errorThrown);
                                $submitBtn.prop('disabled', false).css('opacity', '1');
                                $form[0].submit();
                            }
                        });
                    });
                }

                if (linkSelector !== '') {
                    $(document).off('click.ajaxAddToCart').on('click.ajaxAddToCart', linkSelector, function(e) {
                        e.preventDefault();
                        var $link = $(this);
                        var url = $link.attr('href');

                        if (!url) return;

                        var separator = url.indexOf('?') !== -1 ? '&' : '?';
                        $link.css({'opacity': '0.5', 'pointer-events': 'none'});

                        $.ajax({
                            url: url + separator + 'ajax_add_to_cart=1',
                            type: 'GET',
                            dataType: 'json',
                            success: function(response) {
                                $link.css({'opacity': '', 'pointer-events': ''});
                                handleAjaxResponse(response, null);
                            },
                            error: function(jqXHR, textStatus, errorThrown) {
                                console.error("Ajax Add To Cart link failed. Falling back to normal redirect.", textStatus, errorThrown);
                                $link.css({'opacity': '', 'pointer-events': ''});
                                window.location.href = url;
                            }
                        });
                    });
                }
            });
        })(jQuery);
    } else {
        console.warn('Ajax Add To Cart plugin requires jQuery.');
    }
</script>
