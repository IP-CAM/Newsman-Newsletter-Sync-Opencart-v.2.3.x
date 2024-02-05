<?php echo $header; ?>
<?php echo $column_left; ?>
<div id="content">

    <div class="page-header">
        <div class="container-fluid">
            <h1>Newsman-Opencart Integration</h1>
        </div>
        <div>
            <h3>
                <?php echo $message; ?>
            </h3>
        </div>
    </div>

    <?php if($isOauth) { ?>
    <div id="contentOauth" style="margin: 20px;">

        <!--oauth step-->
        <?php if($oauthStep == 1) { ?>
        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="newsman_oauth" value="Y" />
            <input type="hidden" name="step" value="1" />
            <table class="form-table newsmanTable newsmanTblFixed newsmanOauth">
                <tr>
                    <td>
                        <p class="description"><b>Connect your site with NewsMAN for:</b></p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p class="description">- Subscribers Sync</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p class="description">- Ecommerce Remarketing</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p class="description">- Create and manage forms</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p class="description">- Create and manage popups</p>
                    </td>
                </tr>
                <tr>
                    <td>
                        <p class="description">- Connect your forms to automation</p>
                    </td>
                </tr>
            </table>

            <div style="padding-top: 5px;">
                <a style="background: #ad0100" href="<?php echo $oauthUrl; ?>"
                    class="button button-primary btn btn-primary">Login with NewsMAN</a>
            </div>
        </form>

        <!--List step-->
        <?php } else if($oauthStep == 2) { ?>

        <form method="post" enctype="multipart/form-data">
            <input type="hidden" name="oauthstep2" value="Y" />
            <input type="hidden" name="step" value="1" />
            <input type="hidden" name="creds" value='<?php echo htmlspecialchars($creds, ENT_QUOTES, "UTF-8"); ?>' />
            <table class="form-table newsmanTable newsmanTblFixed newsmanOauth">
                <tr>
                    <td>
                        <select name="newsman_list" id="">
                            <option value="0">-- select list --</option>
                            <?php foreach ($dataLists as $l)
										{ ?>
                            <option value="<?php echo $l['id'] ?>">
                                <?php echo $l['name']; ?>
                            </option>
                            <?php } ?>
                        </select>
                    </td>
                </tr>
            </table>

            <div style="padding-top: 5px;">
                <button type="submit" style="background: #ad0100" class="button button-primary btn btn-primary">Save</a>
            </div>
        </form>

    </div>
    <?php } ?>
    <?php } else { ?>
    <div class="container">
        <div class="col-md-5">
            <div class="form-group">
                <form method="post" id="newsman_form">
                    <div>
                        <label>User Id</label>
                        <input type="text" name="userid" placeholder="user id" value="<?php echo $userid; ?>"
                            class="form-control" />
                        <label>Api Key</label>
                        <input type="text" name="apikey" placeholder="api key" value="<?php echo $apikey; ?>"
                            class="form-control" />
                        <label for="apiallow">Allow API ?</label>
                        <input type="checkbox" id="apiallow" name="apiallow" <?php echo ($apiallow=='on' ) ? 'checked'
                            : '' ; ?>
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
                        <input type="submit" name="newsmanSubmitSaveSegment" value="Save Segment"
                            class="btn btn-primary">
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
                        <input type="submit" name="newsmanSubmitSaveType" value="Save Import Type"
                            class="btn btn-primary">
                    </div>
                    <div>
                        <input type="submit" name="newsmanSubmitList" value="Import" class="btn btn-primary">
                    </div>
                </form>
            </div>
        </div>
        <div class="col-md-12">
            <b>CRON for sync subscribers</b>
            </p>{yoursiteurl}/index.php?route=extension/module/newsman&cron=1&apikey={your_api_key}</p>

            <b>CRON for sync feed products:</b>
            <p>{yoursiteurl}/index.php?route=extension/module/newsman&newsman=products.json&apikey={yourapikey}&cron=true
            </p>

        </div>
    </div>
    <?php } ?>
</div>
<?php echo $footer; ?>