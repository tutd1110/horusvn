<template>
    <div>
        <div class="qrcode-title">Thanh toán chuyển khoản tại đây.</div>
        <el-image
        style="width: 400px; height: 650px; display: block; margin: 0 auto;"
        :src="getQrCodePath()"
        :zoom-rate="1.2"
        :initial-index="4"
        fit="contain"
        />
        <div style="text-align: center; margin-top: 20px;">Nội dung chuyển khoản: CK_tien_com_ho_ten</div>
    </div>
</template>
<script lang="ts" setup>
const getQrCodePath = () =>{
    return window.location.origin + '/' + 'image/order/qr-code-c-xuan.jpeg';
}
</script>
<style lang="scss"></style>
