/**
 * Shared client-side helpers for the cPanel plugin's client area panels and Quick Login card.
 * All requests go through the panelaction endpoint; the session hash is added by the global ajaxSetup.
 */
window.CpanelPanel = window.CpanelPanel || (function ($) {
    var actionUrl = 'index.php?fuse=clients&controller=products&action=panelaction';

    function post(packageId, panel, actionName, data, onComplete) {
        $.ajax({
            type: 'POST',
            url: actionUrl,
            // Routing parameters go last so form data can never override them
            data: $.extend({}, data, { id: packageId, panel: panel, do: actionName }),
            dataType: 'json',
            complete: function (xhr) {
                onComplete(xhr.responseJSON || { success: false });
            }
        });
    }

    function create(packageId, panel) {
        return {
            /**
             * Runs a panel action, calling onSuccess when it succeeds.
             */
            action: function (actionName, data, onSuccess) {
                clientexec.mask();
                post(packageId, panel, actionName, data, function (response) {
                    clientexec.parseResponse(response);
                    if (response.success == true) {
                        onSuccess(response);
                    } else {
                        clientexec.unMask();
                    }
                });
            },

            /**
             * Runs a panel action and reloads the page when it succeeds.
             */
            reloadAction: function (actionName, data) {
                this.action(actionName, data, function () {
                    location.reload();
                });
            },

            /**
             * Runs an action that returns a URL and opens it in a new window.
             * The window is opened during the click so popup blockers allow it, then pointed at the URL.
             */
            openUrlAction: function (actionName, data) {
                var newWindow = window.open('about:blank', '_blank');
                if (newWindow) {
                    newWindow.opener = null;
                }
                clientexec.mask();
                post(packageId, panel, actionName, data, function (response) {
                    clientexec.unMask();
                    if (response.success == true && response.url && newWindow) {
                        newWindow.location = response.url;
                        return;
                    }
                    if (newWindow) {
                        newWindow.close();
                    }
                    clientexec.parseResponse(response);
                });
            },

            /**
             * Asks for confirmation, then runs the action and reloads the page.
             */
            confirmAction: function (title, content, buttonText, actionName, data) {
                var self = this;
                $.confirm({
                    title: title,
                    content: content,
                    buttons: {
                        confirm: {
                            text: buttonText,
                            btnClass: 'btn-danger',
                            action: function () {
                                self.reloadAction(actionName, data);
                            }
                        },
                        cancel: {
                            text: clientexec.lang('Cancel')
                        }
                    }
                });
            },

            /**
             * Sets up a DataTable for a panel list; the last column holds the row buttons and isn't sortable.
             */
            table: function (selector, emptyText, zeroText) {
                return $(selector).DataTable({
                    language: {
                        sEmptyTable: emptyText,
                        zeroRecords: zeroText
                    },
                    columnDefs: [
                        { orderable: false, targets: -1 }
                    ]
                });
            }
        };
    }

    return { create: create };
})(jQuery);