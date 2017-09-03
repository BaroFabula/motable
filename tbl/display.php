
<?php
$no_row = 0;
$no_col = 0;
echo'
    
            <div class="row">
                <table id="datatable" class="table table-striped table-bordered" width="100%" cellspacing="0">
                    <thead>
                    <tr>
                        ';foreach ($fieldnames as $fieldname){echo'
                        <td>'.$fieldname.'</td>
                        ';}echo'
                    </tr>
                    <tr>
                        ';foreach ($fieldnames as $fieldname){echo'
                        <th data-name="'.$fieldname.'">'.$fieldname.'</th>
                        ';}echo'
                    </tr>
                    </thead>
                    <tbody>
                        ';
                    foreach ($rows as $row){echo'
                    <tr>
                        ';
                        foreach ($fieldnames as $fieldname){echo'
                        <td id="cell_'.$no_row.'_'.$no_col.'">';if(isset($row->{$fieldname})){echo $row->{$fieldname};};echo'</td>
                        ';$no_col++;}echo'
                    </tr>
                        ';$no_col = 0; $no_row++;}

                    echo'
                    </tbody>
                </table>
            </div>
';