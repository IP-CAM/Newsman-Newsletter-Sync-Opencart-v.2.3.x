<?php echo $header; ?><?php echo $column_left; ?>
<div id="content">
    <div class="page-header">
        <div class="container-fluid">
            <h1>Newsman-Opencart Integration</h1>
        </div>
        <div>
            <h3><?php echo $message; ?></h3>
        </div>
    </div>
    <div class="container">
        <div class="col-md-5">
            <div class="form-group">
                <form method="post" id="newsman_form">
                    <div>
                        <label>User Id</label>
                        <input type="text" name="userid" placeholder="user id" value="<?php echo $userid; ?>"
                               class="form-control"/>
                        <label>Api Key</label>
                        <input type="text" name="apikey" placeholder="api key" value="<?php echo $apikey; ?>"
                               class="form-control"/>
                        <label for="apiallow">Allow API ?</label>
                        <input type="checkbox" id="apiallow" name="apiallow" <?php echo ($apiallow == 'on') ? 'checked' : ''; ?>
                               class="form-control"/>
                        <input type="submit" name="newsmanSubmit" value="Save" class="btn btn-primary">
                    </div>
                    <div>
                        <label>List</label>
                        <select name="list" class="form-control">
                            <?php echo $list; ?>
                        </select>
                        <input type="submit" name="newsmanSubmitSaveList" value="Save List" class="btn btn-primary">
                    </div>
                    <div style="padding-top: 15px;">
                        <label>Segment (Make sure you select list and save)</label>
                        <select name="segment" class="form-control">
                            <?php echo $segment; ?>
                        </select>
                        <input type="submit" name="newsmanSubmitSaveSegment" value="Save Segment" class="btn btn-primary">
                    </div>
                    <div>
                        <label>Import Type</label>
                        <select name="type" class="form-control">
                            <?php if($type == "customers") { ?>
                            <option selected value="customers">Customers who ordered</option>
                            <option value="subscribers">Customers subscribers</option>
                            <?php } else { ?>
                            <option value="customers">Customers who ordered</option>
                            <option selected value="subscribers">Customers subscribers</option>
                            <?php } ?>
                        </select>
                        <input type="submit" name="newsmanSubmitSaveType" value="Save Import Type" class="btn btn-primary">
                    </div>
                    <div>
                        <input type="submit" name="newsmanSubmitList" value="Import" class="btn btn-primary">
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-12">
            <b>CRON for sync subscribers</b>
            </p>{yoursiteurl}/index.php?route=module/newsman_import&cron=1&apikey={your_api_key}</p>
            
            <b>CRON for sync feed products:</b>
            <p>{yoursiteurl}/index.php?route=module/newsman_import&newsman=products.json&apikey={yourapikey}&cron=true</p>
         
        </div>
    </div>
</div>
<?php echo $footer; ?>
