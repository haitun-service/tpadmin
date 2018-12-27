<!--{body}-->

<div class="panel panel-default">

    <div class="panel-heading">
        <h5 class="panel-title"><?php echo $this->title; ?></h5>
    </div>


    <div class="panel-body">


        <form class="form-horizontal">
            <div class="form-group">
            <?php
            $primaryKey = $this->row->getPrimaryKey();
            $fields = $this->row->getFields();
            $i = 0;
            foreach ($fields as $field) {
                if ($field['disable']) continue;

                $f = $field['field'];
                ?>
                <label class="col-sm-1 control-label"><?php echo $field['name']; ?>：</label>
                <div class="col-sm-3">
                    <p class="form-control-static"><?php echo $this->row->$f; ?></p>
                </div>
                <?php
                $i++;

                if ($i == 3) {
                    echo '</div><div class="form-group">';
                    $i = 0;
                }
            }
            ?>
            </div>

            <div class="form-group">
                <div class="col-sm-7 col-sm-offset-5">
                    <input type="button" value="返回" class="btn btn-info" onclick="history.go(-1);"/>
                </div>
            </div>

        </form>


    </div>

</div>
<!--{/body}-->
