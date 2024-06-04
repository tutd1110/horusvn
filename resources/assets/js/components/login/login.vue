<template>
    <div class="login">
        <h2 class="login-header">Horus Work</h2>
         <a-form class="login-container" :model="formState" autocomplete="off">
            <label>Email</label>
              <a-input v-model:value="formState.email" placeholder="Email" autofocus/>
            <label>Password</label>
              <a-input-password v-model:value="formState.password" placeholder="Password" />
            <div class="submit-area">
                <a-form-item>
                    <single-submit-button type="primary" html-type="submit" :onclick="doLogin">Login</single-submit-button>
                </a-form-item>
            </div>
         </a-form>
    </div>
</template>
<script>
import { Modal } from 'ant-design-vue';
import { ref, h } from 'vue';
import { useI18n } from 'vue-i18n';
import SingleSubmitButton from '../Shared/SingleSubmitButton/SingleSubmitButton.vue';
    export default ({
        components: {
            SingleSubmitButton,
        },
        setup() {
           const formState = ref({email:'', password:''});
           const { t } = useI18n(); //error title

           const doLogin = (event) => {
             event.preventDefault();

             return new Promise((resolve,reject) => {
                const instance = axios.create({
                    withCredentials: true //token approval
                })

                instance.get('/sanctum/csrf-cookie/')
                    .then(response => {
                        instance.post('/login', formState.value)
                            .then(response => {
                                resolve();
                                location.href = response.data.redirect_path;
                            })
                            .catch(error => {
                                reject();
                                Modal.warning({
                                    title: t('message.MSG-TITLE-W'),
                                    content: h('ul', {}, error.response.data.errors.split('<br>').map((error) => { return h('li', error) })),
                                });  
                            }); 
                    });
                })
            }

            return {
                formState,
                doLogin,
            }
        }    
    })
</script>
<style lang="scss">
.login {
    width: 400px;

    @media screen and (max-width:480px) {
        width: 80%;
    }

    margin: 16px auto;
    font-size: 16px;

    background: #FFFFFF;
    box-shadow: 0 0 20px 0 rgba(0, 0, 0, 0.2),
    0 5px 5px 0 rgba(0, 0, 0, 0.24);

    /* Reset top and bottom margins from certain elements */
    .login-header,
    .login p {
        margin-top: 0;
        margin-bottom: 0;
    }

    /* The triangle form is achieved by a CSS hack */
    .login-triangle {
        width: 0;
        margin-right: auto;
        margin-left: auto;
        border: 12px solid transparent;
        border-bottom-color: #28d;
    }

    .login-header {
        background: #28d;
        padding: 20px;
        font-size: 1.4em;
        font-weight: normal;
        text-align: center;
        color: #fff;
    }

    .login-container {
        background: #ebebeb;
        padding: 12px;
    }

    /* Every row inside .login-container is defined with p tags */
    p {
        padding: 12px;
    }

    label {
        display: inline-block;
        color: gray;
        font-size: 1.1em;
        margin-top: 0.6em;
    }

    .ant-input,
    .ant-input-affix-wrapper>input.ant-input {
        box-sizing: border-box;
        display: block;
        width: 100%;
        margin-bottom: 5px;
    }

    .submit-area {
        padding-top: 10px;
        text-align: center;
    }
}
</style>