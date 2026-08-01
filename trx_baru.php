<?php
include("sess_check.php");

// deskripsi halaman
$pagedesc = "Transaksi Baru";
include("layout_top.php");
include("dist/function/format_tanggal.php");
include("dist/function/format_rupiah.php");
$adm = $sess_admid; // Ini untuk admin, disamakan dengan kasir role
$tgl = date('Y-m-d');

if(isset($_POST['simpan'])){
    $adm = $sess_admid;
    $grand=0;
    $tg=$_POST['tgl'];
    $kon=$_POST['kon'];
    $metode_bayar = isset($_POST['metode_bayar']) ? $_POST['metode_bayar'] : 'Tunai';
    $diskon_nota = isset($_POST['diskon_nota']) ? (int)$_POST['diskon_nota'] : 0;
    $plat_nomor = isset($_POST['plat_nomor']) ? $_POST['plat_nomor'] : null;
    $catatan = isset($_POST['catatan']) ? $_POST['catatan'] : null;
    $status_bayar = ($metode_bayar == 'Hutang') ? 'Belum Lunas' : 'Lunas';
    $jatuh_tempo = ($metode_bayar == 'Hutang' && isset($_POST['jatuh_tempo']) && $_POST['jatuh_tempo'])
        ? $_POST['jatuh_tempo'] : null;

    // Generate transaction ID
    $no = date('dmYHis');
    $stt = "Done";
    $stts = "On Process";

    // Process cart items from tmp_trx
    $sql = "SELECT tmp_trx.*, barangjasa.* FROM tmp_trx, barangjasa
            WHERE tmp_trx.id_brg=barangjasa.id_brg
            AND tmp_trx.id_kasir='$adm' AND tmp_trx.status='On Process'"; // Fixed: id_adm → id_kasir
    $query = mysqli_query($conn,$sql);

    $total_item = 0;
    while($res = mysqli_fetch_array($query)){
        $ttl = $res['jml'] * $res['harga'];
        $st = $res['stok'];
        $jml = $res['jml'];
        $newst = $st - $jml;
        $br = $res['id_brg'];
        $jns = $res['jenis'];

        // Update stock for barang
        if($jns == 'barang'){
            $sqlbr = "UPDATE barangjasa SET stok='$newst' WHERE id_brg='$br'";
            mysqli_query($conn, $sqlbr);
        }

        // Calculate item diskon from tmp_trx
        $item_diskon = isset($res['diskon']) ? (int)$res['diskon'] : 0;

        // Insert into trx_detail table (snapshot)
        $sql_detail = "INSERT INTO trx_detail (id_trx, id_brg, nama_snap, jenis_snap, harga_snap, diskon, jml, subtotal)
                       VALUES ('$no', '$br', '{$res['nama']}', '$jns', '{$res['harga']}', '$item_diskon', '$jml', '" . ($ttl - $item_diskon * $jml) . "')";
        mysqli_query($conn, $sql_detail);

        $grand += ($ttl - $item_diskon * $jml);
        $total_item++;
    }

    // Update tmp_trx status
    $sqltmp = "UPDATE tmp_trx SET id_trx='$no', status='$stt' WHERE id_kasir='$adm' AND status='$stts'"; // Fixed: id_adm → id_kasir
    mysqli_query($conn, $sqltmp);

    // Insert into trx table
    $sqltrx = "INSERT INTO trx (id_trx, id_kon, tgl_trx, total, id_adm, metode_bayar, diskon_nota, status_bayar, jatuh_tempo, plat_nomor, catatan)
               VALUES ('$no', '$kon', '$tg', '$grand', '$adm', '$metode_bayar', '$diskon_nota', '$status_bayar', " . ($jatuh_tempo ? "'$jatuh_tempo'" : "NULL") . ", " . ($plat_nomor ? "'$plat_nomor'" : "NULL") . ", " . ($catatan ? "'$catatan'" : "NULL") . ")";
    mysqli_query($conn, $sqltrx);

    // Handle Hutang/Bon payments (if needed for admin)
    if($metode_bayar == 'Hutang' && $grand > 0){
        // This part needs clarification if admin handles customer piutang directly
        // For now, assuming admin only records transaction, actual piutang payment handled elsewhere
        // But for consistency, recording to bayar_piutang if it is for customer.
        // This is a placeholder as the plan doesn't explicitly state admin handling piutang payment itself.
        $sql_piutang = "INSERT INTO bayar_piutang (id_trx, tgl, jumlah, id_adm) VALUES ('$no', '$tg', '$grand', '$adm')";
        // mysqli_query($conn, $sql_piutang); // Uncomment if admin also adds piutang record here
    }

    if(mysqli_affected_rows($conn) > 0){
        echo "<script type='text/javascript'> document.location = 'trx.php'; </script>";
    }
}

?>
<!-- top of file -->
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Transaksi Baru</h1>
            </div><!-- /.col-lg-12 -->
        </div><!-- /.row -->

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-12">
                <div class="panel panel-default">
                    <div class="panel-heading">
                        <a href="tmp_tambah.php" class="btn btn-warning">Tambah</a>
                    </div>
                    <form class="form-horizontal" method="POST" enctype="multipart/form-data">
                        <div class="panel-body">
                            <div class="form-group">
                                <label class="control-label col-sm-3">Konsumen</label>
                                <div class="col-sm-4">
                                    <select name="kon" id="kon" class="form-control" required>
                                        <option value="">==== Pilih Konsumen ====</option>
                                        <option value="0">Umum</option>
                                        <?php
                                        $sql_brg = "SELECT * FROM konsumen WHERE id_kon!='0' ORDER BY nama_kon ASC";
                                        $ress_brg = mysqli_query($conn, $sql_brg);
                                        while($li = mysqli_fetch_array($ress_brg)){
                                            echo '<option value="'. htmlspecialchars($li['id_kon']) .'">'. htmlspecialchars($li['nama_kon']).'</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-3">Metode Bayar</label>
                                <div class="col-sm-4">
                                    <select name="metode_bayar" id="metode_bayar" class="form-control">
                                        <option value="Tunai">Tunai</option>
                                        <option value="Transfer">Transfer</option>
                                        <option value="Hutang">Hutang/Bon</option>
                                    </select>
                                </div>
                            </div>

                            <div class="form-group metode-details" style="display:none;">
                                <label class="control-label col-sm-3">Jatuh Tempo</label>
                                <div class="col-sm-4">
                                    <input type="date" name="jatuh_tempo" class="form-control">
                                </div>
                            </div>

                            <div class="form-group">
                                <label class="control-label col-sm-3">Diskon Nota</label>
                                <div class="col-sm-4">
                                    <input type="number" name="diskon_nota" class="form-control" placeholder="0" min="0">
                                </div>
                            </div>

                            <div class="form-group plat-details" style="display:none;">
                                <label class="control-label col-sm-3">Plat Nomor</label>
                                <div class="col-sm-4">
                                    <input type="text" name="plat_nomor" class="form-control" placeholder="Contoh: B 1234 ABC">
                                </div>
                            </div>

                            <div class="form-group catatan-details" style="display:none;">
                                <label class="control-label col-sm-3">Catatan</label>
                                <div class="col-sm-8">
                                    <textarea name="catatan" class="form-control" placeholder="Catatan tambahan..."></textarea>
                                </div>
                            </div>
                        </div>

                        <div class="panel-body">
                            <table class="table table-striped table-bordered table-hover" id="tabel-data">
                                <thead>
                                    <tr>
                                        <th width="1%">No</th>
                                        <th width="10%">Nama</th>
                                        <th width="10%">Jumlah</th>
                                        <th width="10%">Harga Satuan</th>
                                        <th width="10%">Total Harga</th>
                                        <th width="2%">Opsi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    $i = 1;
                                    $grand = 0;
                                    $sql = "SELECT tmp_trx.*, barangjasa.*, admin.* FROM tmp_trx, barangjasa, admin
                                            WHERE tmp_trx.id_brg=barangjasa.id_brg AND tmp_trx.id_kasir=admin.id_adm
                                            AND tmp_trx.status='On Process' AND tmp_trx.id_kasir='$adm'
                                            ORDER BY barangjasa.nama ASC";
                                    $ress = mysqli_query($conn, $sql);
                                    while($data = mysqli_fetch_array($ress)){
                                        $ttl = $data['jml'] * $data['harga'];
                                        $diskon = isset($data['diskon']) ? (int)$data['diskon'] : 0;
                                        $ttl_final = $ttl - ($diskon * $data['jml']);
                                        echo '<tr>';
                                        echo '<td class="text-center">'. $i .'</td>';
                                        echo '<td class="text-center">'. htmlspecialchars($data['nama']) .'</td>';
                                        echo '<td class="text-center">'. htmlspecialchars($data['jml']) .'</td>';
                                        echo '<td class="text-center">'. format_rupiah($data['harga']) .'</td>';
                                        echo '<td class="text-center">'. format_rupiah($ttl_final) .'</td>';
                                        echo '<td class="text-center">'; ?>
                                            <a href="trxtmp_hapus.php?id=<?php echo htmlspecialchars($data['id_tmp']); ?>"
                                               onclick="return confirm('Apakah anda yakin akan menghapus <?php echo htmlspecialchars($data['nama']); ?>?');"
                                               class="btn btn-danger btn-xs">Hapus</a>
                                        <?php echo '</td>';
                                        echo '</tr>';
                                        $i++;
                                        $grand += $ttl_final;
                                    }
                                    ?>
                                </tbody>
                                <tfoot>
                                    <tr>
                                        <th colspan="4" class="text-center">Subtotal</th>
                                        <th class="text-right"><?php echo format_rupiah($grand); ?></th>
                                        <th class="text-center"></th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-center">Diskon Nota</th>
                                        <th class="text-right">
                                            <?php
                                            $diskon_nota = isset($_POST['diskon_nota']) ? (int)$_POST['diskon_nota'] : 0;
                                            echo format_rupiah($diskon_nota);
                                            ?>
                                        </th>
                                        <th class="text-center"></th>
                                    </tr>
                                    <tr>
                                        <th colspan="4" class="text-center">Total Akhir</th>
                                        <th class="text-right">
                                            <?php echo format_rupiah($grand - $diskon_nota); ?>
                                        </th>
                                        <th class="text-center"></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        <div class="panel-body">
                            <input type="hidden" name="tgl" class="form-control" value="<?php echo htmlspecialchars($tgl); ?>">
                            <input type="hidden" name="metode_bayar" value="<?php echo htmlspecialchars(isset($_POST['metode_bayar']) ? $_POST['metode_bayar'] : 'Tunai'); ?>">
                            <input type="hidden" name="diskon_nota" value="<?php echo htmlspecialchars(isset($_POST['diskon_nota']) ? $_POST['diskon_nota'] : '0'); ?>">
                        </div>
                        <div class="panel-footer">
                            <button type="submit" name="simpan" class="btn btn-success">Simpan Transaksi</button>
                        </div>
                    </form>
                </div><!-- /.panel -->
            </div><!-- /.col-lg-12 -->
        </div><!-- /.row -->
    </div><!-- /.container-fluid -->
</div><!-- /#page-wrapper -->
<!-- bottom of file -->
<script type="text/javascript">
$(document).ready(function() {
    $('#tabel-data').DataTable({
        "responsive": true,
        "processing": true,
        "columnDefs": [
            { "orderable": false, "targets": [5] }
        ]
    });

    $('#tabel-data').parent().addClass("table-responsive");

    // Show/hide metode_bayar related fields
    $('#metode_bayar').on('change', function() {
        var val = $(this).val();
        if(val == 'Hutang'){
            $('.metode-details').show();
            $('.plat-details').show();
            $('.catatan-details').show();
        } else {
            $('.metode-details').hide();
            $('.plat-details').hide();
            $('.catatan-details').hide();
        }
    });
});
</script>
<?php
include("layout_bottom.php");
?>