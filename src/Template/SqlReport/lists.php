<!--{head}-->
<script>

    function getHeight() {
        return $(window).height() - $("#searchForm").height() - 35;
    }

    function resizeTable() {
        $("#table").bootstrapTable('resetView', {
            height: getHeight()
        });
    }

    var g_bLoaded = false;
    $(function(){
        <?php
        $autoLoadData = true;
        if (isset($this->config['autoLoadData'])) {
            $autoLoadData = $this->config['autoLoadData'];
        }

        if ($autoLoadData) {
            ?>
            g_bLoaded = true;
            $("#table").bootstrapTable({
                height: getHeight()
            });
            <?php
        }
        ?>

        $(window).resize(function () {
            resizeTable();
        });

        $('#btn_search').click(function(){  // 点击查询
            if (g_bLoaded) {
                $("#table").bootstrapTable('refresh',{query: {offset: 0}});
            } else {
                g_bLoaded = true;
                $("#table").bootstrapTable({
                    height: getHeight()
                });
            }
        });

        $(".search-form").submit(function () {
            $('#btn_search').trigger("click");
            return false;
        });
    });

    function loadData(params) {
        $.extend(params.data, $(".search-form").serializeObject());
        $.post(location.href, params.data,function(result){
            params.success(result);
            resizeTable();
        });
    }

    function exportData() {
        var $this = $(this);

        var eForm = document.createElement("form");
        eForm.action = "<?php echo \Haitun\Service\TpAdmin\Util\Url::encode('export'); ?>";
        eForm.target = "_blank";
        eForm.method = "post";
        eForm.style.display = "none";
        var params = $(".search-form").serializeObject();

        for (var x in params) {
            var e = document.createElement("input");
            e.name = x;
            e.value = params[x];
            eForm.appendChild(e);
        }

        document.body.appendChild(eForm);
        eForm.submit();

        setTimeout(function () {
            $(eForm).remove();
        }, 3000);
        return false;
    }
</script>
<!--{/head}-->


<!--{body}-->
<form id="searchForm" class="search-form" style="border-bottom: #eee 1px solid; padding-bottom: 10px;">
    <div class="row" style="margin-bottom: 5px;">
        <div class="clearfix">
            <?php
            $colCount = 0;
            foreach($this->config['search'] as $key => $search) {
                $cols = 3;
                if (isset($search['cols'])) {
                    $cols = $search['cols'];
                }

                $colCount += $cols;
                if ($colCount > 12) {
                    $colCount = $cols;
                    echo '</div></div><div class="row" style="margin-bottom: 5px;"><div class="clearfix">';
                }

                $driver = $search['driver'];
                $searchDriver = new $driver($key, $search);

                echo '<div class="col-md-'.$cols.'">';
                echo $searchDriver->getHtml();
                echo '</div>';
            }
            ?>
        </div>
    </div>

    <div id="toolbar">
        <button type="button" class="btn btn-primary" id="btn_search">查询</button>
        <button type="reset" class="btn btn-warning">重置</button>
        <button type="button" class="btn btn-info" onclick="exportData();">导出</button>
    </div>

</form>

<?php
$pagination = true;
if (isset($this->config['pagination'])) {
    $pagination = $this->config['pagination'];
}
?>
<table class="table table-striped table-bordered table-hover" id="table"
       data-ajax="loadData"
       data-toolbar="#toolbar"
       <?php
       if (isset($this->config['sql']['orderBy'])) {
           $orderBy = $this->config['sql']['orderBy'];
           $orderByDir = isset($this->config['sql']['orderByDir']) ? $this->config['sql']['orderByDir'] : 'DESC';
           ?>
           data-sort-name="<?php echo $orderBy; ?>"
           data-sort-order="<?php echo $orderByDir; ?>"
            <?php
       }
       ?>
       data-show-refresh="true"
       data-show-columns="true"
       data-cookie="true"
       data-cookie-id-table=""
       data-side-pagination="<?php echo $pagination?'server':'client'; ?>"
       data-stickyHeader="true"
       data-stickyHeaderOffsetY="0"
       data-pagination="true"
       data-cache="false"
       data-page-size="10"
       data-page-list="[10,25,50,100]"
>
    <thead>
    <tr>
        <?php
        foreach ($this->config['fields'] as $key => $field) {
            ?>
            <th data-align="center" data-field="<?php echo $key; ?>"><?php echo $field['name']; ?></th>
            <?php
        }
        ?>
    </tr>
    </thead>
</table>
<!--{/body}-->
