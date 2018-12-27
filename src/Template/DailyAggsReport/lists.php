<!--{head}-->
<script>

    function getHeight() {
        return $(window).height()-140;
    }

    function resizeTable() {
        $("#table").bootstrapTable('resetView', {
            height: getHeight()
        });
    }

    $(function(){
        $("#table").bootstrapTable({
            height: getHeight()
        });

        $(window).resize(function () {
            resizeTable();
        });

        $('#btn_search').click(function(){  // 点击查询
            $("#table").bootstrapTable('refresh',{query: {offset: 0}});
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
<div class="row">
    <div class="col-sm-12">
        <div class="ibox float-e-margins">
            <div class="ibox-title">
                <div class="ibox-content">
                    <form id="searchForm" class="search-form">
                        <div class="row">
                            <div class="clearfix">
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <span class="input-group-addon bold">内单号</span>
                                        <input class="form-control" type="text" id="orders_unique_id" name="orders_unique_id"/>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="input-group">
                                        <span class="input-group-addon bold">平台</span>
                                        <input class="form-control" type="text" id="platform_code" name="platform_code"/>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="input-group">
                                        <label class="input-group-addon bold">创建时间</label>
                                        <input class="form-control" type="text" id="create_start" name="create_start"
                                               onclick="laydate({istime: true, format: 'YYYY-MM-DD'})" placeholder="开始日期" readonly/>
                                        <span class="input-group-addon">~</span>
                                        <input class="form-control" type="text" id="create_end" name="create_end"
                                               onclick="laydate({istime: true, format: 'YYYY-MM-DD'})" placeholder="结束日期" readonly/>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <hr/>
                        <button type="button" class="btn btn-primary" id="btn_search">查询</button>
                        <button type="reset" class="btn btn-warning">重置</button>
                        <button type="button" class="btn btn-info" onclick="exportData();">导出</button>
                    </form>

                    <table class="table table-striped table-bordered table-hover" id="table"
                           data-ajax="loadData"
                           data-toolbar="#toolbar"
                           data-sort-name="aggs_date"
                           data-sort-order="desc"
                           data-show-refresh="true"
                           data-show-toggle="true"
                           data-show-columns="true"
                           data-cookie="true"
                           data-cookie-id-table=""
                           data-side-pagination="server"
                           data-stickyHeader="true"
                           data-stickyHeaderOffsetY="0"
                           data-pagination="true"
                           data-cache="false"
                           data-page-size="10"
                           data-page-list="[10,25,50,100]"
                    >
                        <thead>
                        <tr>
                            <th data-align="center" data-sortable="true" data-field="aggs_date">日期</th>
                            <th data-align="center" data-sortable="true" data-field="aggs_key"><?php echo $this->config['aggsKey']['name']; ?></th>
                            <?php
                            foreach ($this->config['aggsValues'] as $i => $aggsValue) {
                                 ?>
                                <th data-align="center" data-field="aggs_value_<?php echo $i; ?>"><?php echo $aggsValue['name']; ?></th>
                                <?php
                            }
                            ?>
                        </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
<!--{/body}-->
