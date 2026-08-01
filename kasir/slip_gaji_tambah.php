<?php
include("sess_check.php");

$id_pegawai = isset($_GET['id_pegawai']) ? (int)$_GET['id_pegawai'] : 0;
if($id_pegawai == 0) { 
    // Jika tidak ada id_pegawai di GET, tampilkan dropdown pemilihan pegawai
    $pilihPegawai = true;
} else {
    $pilihPegawai = false;
    $sql = "SELECT * FROM pegawai WHERE id_pegawai=$id_pegawai AND status='Aktif'";
    $res = mysqli_query($conn, $sql);
    if(mysqli_num_rows($res) == 0) { 
        header("Location: slip_gaji.php?msg=".urlencode('Pegawai tidak ditemukan')); 
        exit; 
    }
    $p = mysqli_fetch_array($res);
}

$pagedesc = "Buat Slip Gaji";
$menuparent = "pegawai";
include("layout_top.php");
include("dist/function/format_rupiah.php");
?>
<!-- Page Content -->
<div id="page-wrapper">
    <div class="container-fluid">
        <div class="row">
            <div class="col-lg-12">
                <h1 class="page-header">Buat Slip Gaji</h1>
            </div>
        </div>

        <div class="row">
            <div class="col-lg-12"><?php include("layout_alert.php"); ?></div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="panel panel-default">
                    <div class="panel-heading"><h3>Form Slip Gaji</h3></div>
                    <div class="panel-body">
                        <form action="slip_gaji_insert.php" method="POST" class="form-horizontal" onsubmit="return hitungTotal()">
                            <?php if($pilihPegawai): ?>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Pegawai <span class="text-danger">*</span></label>
                                <div class="col-sm-6">
                                    <select name="id_pegawai" id="id_pegawai" class="form-control" required onchange="loadPegawaiData()">
                                        <option value="">== Pilih Pegawai ==</option>
                                        <?php
                                        $sql = "SELECT * FROM pegawai WHERE status='Aktif' ORDER BY nama ASC";
                                        $res = mysqli_query($conn, $sql);
                                        while($pg = mysqli_fetch_array($res)) {
                                            echo '<option value="'.$pg['id_pegawai'].'" 
                                                data-gaji="'.$pg['gaji_pokok'].'" 
                                                data-tarif="'.$pg['tarif_lembur'].'"
                                                data-nama="'.$pg['nama'].'"
                                                data-jabatan="'.$pg['jabatan'].'">'.$pg['nama'].' ('.$pg['jabatan'].')</option>';
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                            <?php else: ?>
                            <input type="hidden" name="id_pegawai" value="<?php echo $p['id_pegawai']; ?>">
                            <div class="form-group">
                                <label class="control-label col-sm-3">Pegawai</label>
                                <div class="col-sm-6">
                                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($p['nama'].' ('.$p['jabatan'].')'); ?>" readonly>
                                </div>
                            </div>
                            <?php endif; ?>

                            <div class="form-group">
                                <label class="control-label col-sm-3">Periode <span class="text-danger">*</span></label>
                                <div class="col-sm-6">
                                    <input type="text" name="periode" class="form-control" placeholder="Contoh: Juli 2026" required 
                                           value="<?php echo date('F Y'); ?>">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Gaji Pokok (Rp)</label>
                                <div class="col-sm-6">
                                    <input type="number" name="gaji_pokok" id="gaji_pokok" class="form-control" min="0" 
                                           value="<?php echo $pilihPegawai ? 0 : $p['gaji_pokok']; ?>" required oninput="hitungTotal()">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tarif Lembur/jam (Rp)</label>
                                <div class="col-sm-6">
                                    <input type="number" name="tarif_lembur" id="tarif_lembur" class="form-control" min="0" 
                                           value="<?php echo $pilihPegawai ? 0 : $p['tarif_lembur']; ?>" required oninput="hitungTotal()">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Jam Lembur</label>
                                <div class="col-sm-6">
                                    <input type="number" name="jam_lembur" id="jam_lembur" class="form-control" min="0" value="0" oninput="hitungTotal()">
                                    <small class="text-muted">Isi 0 jika tidak ada lembur</small>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Potongan (Rp)</label>
                                <div class="col-sm-6">
                                    <input type="number" name="potongan" id="potongan" class="form-control" min="0" value="0" oninput="hitungTotal()">
                                    <small class="text-muted">Pinjaman, keterlambatan, dll</small>
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Tanggal Bayar</label>
                                <div class="col-sm-6">
                                    <input type="date" name="tgl_bayar" class="form-control" value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>

                            <!-- Calculated fields (readonly) -->
                            <hr>
                            <div class="form-group">
                                <label class="control-label col-sm-3">Bonus Lembur (Rp)</label>
                                <div class="col-sm-6">
                                    <input type="text" id="bonus_lembur" class="form-control" value="Rp0" readonly style="font-weight:bold; background:#f5f5f5">
                                    <input type="hidden" name="bonus_lembur" id="bonus_lembur_hidden" value="0">
                                </div>
                            </div>
                            <div class="form-group">
                                <label class="control-label col-sm-3"><strong>Gaji Bersih (Rp)</strong></label>
                                <div class="col-sm-6">
                                    <input type="text" id="gaji_bersih" class="form-control" value="Rp0" readonly style="font-weight:bold; font-size:18px; background:#e8f5e9; color:#2e7d32">
                                    <input type="hidden" name="gaji_bersih" id="gaji_bersih_hidden" value="0">
                                </div>
                            </div>

                            <div class="form-group">
                                <div class="col-sm-offset-3 col-sm-6">
                                    <button type="submit" name="simpan" class="btn btn-success btn-lg">Simpan Slip Gaji</button>
                                    <a href="slip_gaji.php" class="btn btn-default">Batal</a>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function rp(n){ return 'Rp'+(n||0).toLocaleString('id-ID'); }

function loadPegawaiData() {
    var opt = document.getElementById('id_pegawai').selectedOptions[0];
    if(opt && opt.value) {
        var gaji = parseInt(opt.getAttribute('data-gaji')) || 0;
        var tarif = parseInt(opt.getAttribute('data-tarif')) || 0;
        document.getElementById('gaji_pokok').value = gaji;
        document.getElementById('tarif_lembur').value = tarif;
        hitungTotal();
    }
}

function hitungTotal() {
    var gaji = parseInt(document.getElementById('gaji_pokok').value) || 0;
    var tarif = parseInt(document.getElementById('tarif_lembur').value) || 0;
    var jam = parseInt(document.getElementById('jam_lembur').value) || 0;
    var pot = parseInt(document.getElementById('potongan').value) || 0;
    
    var bonus = tarif * jam;
    var bersih = gaji + bonus - pot;
    
    document.getElementById('bonus_lembur').value = rp(bonus);
    document.getElementById('bonus_lembur_hidden').value = bonus;
    document.getElementById('gaji_bersih').value = rp(bersih);
    document.getElementById('gaji_bersih_hidden').value = bersih;
}

document.addEventListener('DOMContentLoaded', function(){
    hitungTotal();
    <?php if(!$pilihPegawai): ?>loadPegawaiData();<?php endif; ?>
});
</script>

<?php include("layout_bottom.php"); ?>