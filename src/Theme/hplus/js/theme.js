$.fn.serializeObject = function() {
    var o = {};
    var a = this.serializeArray();
    $.each(a, function() {
        if (o[this.name]) {
            if (!o[this.name].push) {
                o[this.name] = [o[this.name]];
            }
            o[this.name].push(this.value || '');
        } else {
            o[this.name] = this.value || '';
        }
    });
    return o;
};


function openInNewTab(sTitle, sUrl) {
    parent.openInNewTab(sTitle, sUrl);
}

function closeTab(sUrl) {
    parent.closeTab(sUrl);
}

function refreshParentTab(sUrl) {
    parent.refreshParentTab(sUrl);
}

$(function () {
    $(".open-in-new-tab").click(function () {
        var $this = $(this);

        var sTitle = $this.data("title");
        if (!sTitle) sTitle = $this.text();
        if (!sTitle) sTitle = $this.val();

        var sUrl = $this.data("url");
        if (!sUrl) sUrl = $this.attr("href");

        parent.openInNewTab(sTitle, sUrl);
        return false;
    })
});

function refreshTable() {
    $("#table").bootstrapTable('refresh');
}