<script setup lang="ts">
import { ref, computed, reactive } from 'vue';
import { useRouter } from 'vue-router';
import JButton from '@/components/JButton.vue';

const router = useRouter();

type Mode = 'login' | 'register';

const mode = ref<Mode>('login');
const loading = ref(false);
const error = ref('');
const showPassword = ref(false);
const agreed = ref(false);

const form = reactive({
  email: '',
  password: '',
  confirmPassword: '',
  captcha: '',
});

const emailError = ref('');
const passwordError = ref('');
const captchaError = ref('');

const captchaText = ref('');
function refreshCaptcha() {
  // 待接入：M3 阶段从 /api/auth/captcha 拉取图形验证码
  const chars = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';
  let s = '';
  for (let i = 0; i < 4; i++) {
    s += chars[Math.floor(Math.random() * chars.length)];
  }
  captchaText.value = s;
}
refreshCaptcha();

const canSubmit = computed(() => {
  if (mode.value === 'register') {
    return (
      form.email.length > 0 &&
      form.password.length >= 8 &&
      form.confirmPassword === form.password &&
      form.captcha.length > 0 &&
      agreed.value
    );
  }
  return form.email.length > 0 && form.password.length > 0;
});

function switchMode(m: Mode) {
  mode.value = m;
  error.value = '';
  emailError.value = '';
  passwordError.value = '';
  captchaError.value = '';
}

function validate(): boolean {
  emailError.value = '';
  passwordError.value = '';
  captchaError.value = '';
  error.value = '';

  // 邮箱格式
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
  if (!emailRegex.test(form.email)) {
    emailError.value = '请输入有效的邮箱地址';
    return false;
  }

  // 密码长度
  if (form.password.length < 8) {
    passwordError.value = '密码至少 8 位';
    return false;
  }

  // 注册时校验
  if (mode.value === 'register') {
    if (form.confirmPassword !== form.password) {
      passwordError.value = '两次输入的密码不一致';
      return false;
    }
    if (form.captcha.toUpperCase() !== captchaText.value) {
      captchaError.value = '验证码错误';
      return false;
    }
    if (!agreed.value) {
      error.value = '请先阅读并同意用户协议';
      return false;
    }
  }

  return true;
}

async function handleSubmit() {
  if (!canSubmit.value || !validate()) return;
  loading.value = true;
  error.value = '';

  try {
    // 待接入：M3 阶段实现真实接口
    // const endpoint = mode.value === 'login' ? '/auth/login' : '/auth/register';
    // const payload =
    //   mode.value === 'login'
    //     ? { email: form.email, password: form.password }
    //     : { email: form.email, password: form.password, captcha: form.captcha };
    // const { data } = await http.post<ApiResponse<{ token: string }>>(endpoint, payload);
    // if (data.code !== 0) {
    //   error.value = data.message;
    //   return;
    // }
    // localStorage.setItem('token', data.data.token);
    await new Promise(r => setTimeout(r, 600));

    router.push(mode.value === 'login' ? '/user' : '/');
  } catch (e) {
    error.value = '网络错误，请稍后重试';
  } finally {
    loading.value = false;
  }
}

function loginWithGithub() {
  // 待接入：M3 阶段接入 OAuth 跳转
}
</script>

<template>
  <div class="login">
    <!-- 左侧装饰区（桌面） -->
    <aside class="login__aside" aria-hidden="true">
      <div class="login__aside-content">
        <router-link to="/" class="login__aside-logo">
          <span class="login__aside-logo-mark">
            <svg width="32" height="32" viewBox="0 0 28 28" fill="none">
              <circle cx="12" cy="12" r="7" stroke="currentColor" stroke-width="2"/>
              <path d="M17.5 17.5L23 23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </span>
          <span class="login__aside-logo-text">jisou</span>
        </router-link>

        <h2 class="login__aside-title">
          一个入口<br />搜遍全网资源
        </h2>
        <p class="login__aside-desc">
          聚合主流网盘与 Telegram 公开频道，统一去重排序，让资源发现更快、更准。
        </p>

        <ul class="login__aside-features">
          <li>
            <span class="login__aside-feature-dot" />
            <span>跨平台聚合 · 5+ 数据源并行查询</span>
          </li>
          <li>
            <span class="login__aside-feature-dot" />
            <span>智能去重 · URL 规范化与相关度排序</span>
          </li>
          <li>
            <span class="login__aside-feature-dot" />
            <span>失效检测 · 周期性链接存活监控</span>
          </li>
          <li>
            <span class="login__aside-feature-dot" />
            <span>个人中心 · 收藏 / 历史 / 举报</span>
          </li>
        </ul>

        <p class="login__aside-foot">
          仅索引公开分享链接与元数据，不存储实体文件
        </p>
      </div>

      <!-- 装饰图案 -->
      <div class="login__aside-pattern" aria-hidden="true" />
    </aside>

    <!-- 右侧表单 -->
    <main class="login__main">
      <div class="login__main-inner">
        <!-- 移动端 Logo -->
        <router-link to="/" class="login__mobile-logo" aria-label="jisou 首页">
          <span class="login__mobile-logo-mark" aria-hidden="true">
            <svg width="28" height="28" viewBox="0 0 28 28" fill="none">
              <circle cx="12" cy="12" r="7" stroke="currentColor" stroke-width="2"/>
              <path d="M17.5 17.5L23 23" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
            </svg>
          </span>
          <span class="login__mobile-logo-text">jisou</span>
        </router-link>

        <div class="login__card-wrap animate-fade-up">
          <!-- 模式切换 -->
          <div class="login__tabs" role="tablist">
            <button
              type="button"
              role="tab"
              class="login__tab"
              :class="{ 'is-active': mode === 'login' }"
              :aria-selected="mode === 'login'"
              @click="switchMode('login')"
            >
              登录
            </button>
            <button
              type="button"
              role="tab"
              class="login__tab"
              :class="{ 'is-active': mode === 'register' }"
              :aria-selected="mode === 'register'"
              @click="switchMode('register')"
            >
              注册
            </button>
            <span class="login__tabs-indicator" :class="{ 'is-right': mode === 'register' }" aria-hidden="true" />
          </div>

          <!-- 标题 -->
          <div class="login__head">
            <h1 class="login__title">
              {{ mode === 'login' ? '欢迎回来' : '创建账号' }}
            </h1>
            <p class="login__subtitle">
              {{ mode === 'login' ? '登录后可同步收藏与搜索历史' : '注册后即可使用收藏、历史与举报功能' }}
            </p>
          </div>

          <!-- 错误提示 -->
          <div v-if="error" class="login__error" role="alert">
            <span class="login__error-icon" aria-hidden="true">
              <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                <circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.4"/>
                <path d="M8 5v3.5M8 11v.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
              </svg>
            </span>
            <span>{{ error }}</span>
          </div>

          <!-- 表单 -->
          <form class="login__form" @submit.prevent="handleSubmit">
            <!-- 邮箱 -->
            <div class="login__field">
              <label class="login__label" for="email">邮箱</label>
              <div class="login__input-wrap" :class="{ 'is-error': emailError }">
                <span class="login__input-icon" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <rect x="2" y="3" width="12" height="10" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M3 4l5 4 5-4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                  </svg>
                </span>
                <input
                  id="email"
                  v-model="form.email"
                  type="email"
                  class="login__input"
                  placeholder="you@example.com"
                  autocomplete="email"
                />
              </div>
              <p v-if="emailError" class="login__field-error">{{ emailError }}</p>
            </div>

            <!-- 密码 -->
            <div class="login__field">
              <label class="login__label" for="password">密码</label>
              <div class="login__input-wrap" :class="{ 'is-error': passwordError }">
                <span class="login__input-icon" aria-hidden="true">
                  <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <rect x="3" y="7" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M5 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.4"/>
                  </svg>
                </span>
                <input
                  id="password"
                  v-model="form.password"
                  :type="showPassword ? 'text' : 'password'"
                  class="login__input"
                  placeholder="至少 8 位字符"
                  autocomplete="current-password"
                />
                <button
                  type="button"
                  class="login__input-action"
                  :aria-label="showPassword ? '隐藏密码' : '显示密码'"
                  @click="showPassword = !showPassword"
                >
                  <svg v-if="showPassword" width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z" stroke="currentColor" stroke-width="1.4"/>
                    <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.4"/>
                  </svg>
                  <svg v-else width="16" height="16" viewBox="0 0 16 16" fill="none">
                    <path d="M1 8s2.5-5 7-5 7 5 7 5-2.5 5-7 5-7-5-7-5z" stroke="currentColor" stroke-width="1.4"/>
                    <circle cx="8" cy="8" r="2" stroke="currentColor" stroke-width="1.4"/>
                    <path d="M2 2l12 12" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                  </svg>
                </button>
              </div>
              <p v-if="passwordError" class="login__field-error">{{ passwordError }}</p>
            </div>

            <!-- 注册时：确认密码 + 验证码 -->
            <template v-if="mode === 'register'">
              <div class="login__field">
                <label class="login__label" for="confirm">确认密码</label>
                <div class="login__input-wrap" :class="{ 'is-error': passwordError }">
                  <span class="login__input-icon" aria-hidden="true">
                    <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                      <rect x="3" y="7" width="10" height="7" rx="1.5" stroke="currentColor" stroke-width="1.4"/>
                      <path d="M5 7V5a3 3 0 0 1 6 0v2" stroke="currentColor" stroke-width="1.4"/>
                    </svg>
                  </span>
                  <input
                    id="confirm"
                    v-model="form.confirmPassword"
                    :type="showPassword ? 'text' : 'password'"
                    class="login__input"
                    placeholder="再次输入密码"
                    autocomplete="new-password"
                  />
                </div>
              </div>

              <div class="login__field">
                <label class="login__label" for="captcha">图形验证码</label>
                <div class="login__captcha-row">
                  <div class="login__input-wrap" :class="{ 'is-error': captchaError }">
                    <span class="login__input-icon" aria-hidden="true">
                      <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                        <path d="M8 1L2 4v4c0 4 6 7 6 7s6-3 6-7V4l-6-3z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/>
                      </svg>
                    </span>
                    <input
                      id="captcha"
                      v-model="form.captcha"
                      type="text"
                      class="login__input"
                      placeholder="不区分大小写"
                      maxlength="4"
                      autocomplete="off"
                    />
                  </div>
                  <button
                    type="button"
                    class="login__captcha"
                    :aria-label="`验证码：${captchaText}，点击刷新`"
                    @click="refreshCaptcha"
                  >
                    <span class="login__captcha-text">{{ captchaText }}</span>
                    <span class="login__captcha-refresh" aria-hidden="true">
                      <svg width="12" height="12" viewBox="0 0 12 12" fill="none">
                        <path d="M2 6a4 4 0 0 1 7-2.5M10 6a4 4 0 0 1-7 2.5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
                        <path d="M9 1v3H6M3 11V8h3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
                      </svg>
                    </span>
                  </button>
                </div>
                <p v-if="captchaError" class="login__field-error">{{ captchaError }}</p>
              </div>

              <label class="login__agreement">
                <input v-model="agreed" type="checkbox" class="login__checkbox" />
                <span class="login__agreement-text">
                  我已阅读并同意
                  <a href="#" class="login__agreement-link">用户协议</a>
                  与
                  <a href="#" class="login__agreement-link">隐私政策</a>
                </span>
              </label>
            </template>

            <!-- 登录时：忘记密码 -->
            <div v-else class="login__form-extra">
              <label class="login__remember">
                <input type="checkbox" class="login__checkbox" />
                <span>记住我</span>
              </label>
              <a href="#" class="login__forgot">忘记密码？</a>
            </div>

            <!-- 提交按钮 -->
            <JButton
              type="primary"
              size="large"
              block
              :loading="loading"
              :disabled="!canSubmit"
              @click="handleSubmit"
            >
              {{ mode === 'login' ? '登录' : '注册并登录' }}
            </JButton>
          </form>

          <!-- 分割线 -->
          <div class="login__divider">
            <span class="login__divider-text">或</span>
          </div>

          <!-- 第三方登录 -->
          <div class="login__oauth">
            <button type="button" class="login__oauth-btn" @click="loginWithGithub">
              <svg width="18" height="18" viewBox="0 0 16 16" fill="currentColor">
                <path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38v-1.34c-2.22.48-2.69-1.07-2.69-1.07-.36-.92-.89-1.17-.89-1.17-.73-.5.06-.49.06-.49.81.06 1.23.83 1.23.83.72 1.23 1.88.87 2.34.67.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82a7.67 7.67 0 0 1 2-.27c.68 0 1.36.09 2 .27 1.53-1.03 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.28.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48v2.19c0 .21.15.46.55.38A8.01 8.01 0 0 0 16 8c0-4.42-3.58-8-8-8z"/>
              </svg>
              <span>GitHub 登录</span>
            </button>
          </div>

          <p class="login__foot">
            {{ mode === 'login' ? '还没有账号？' : '已有账号？' }}
            <button
              type="button"
              class="login__foot-link"
              @click="switchMode(mode === 'login' ? 'register' : 'login')"
            >
              {{ mode === 'login' ? '立即注册' : '去登录' }}
            </button>
          </p>
        </div>

        <p class="login__legal">
          登录即表示同意仅将本站用于学习研究目的，禁止用于任何违反法律法规的用途
        </p>
      </div>
    </main>
  </div>
</template>

<style lang="scss" scoped>
.login {
  min-height: 100vh;
  display: grid;
  grid-template-columns: 1fr 1fr;

  @media (max-width: $breakpoint-tablet) {
    grid-template-columns: 1fr;
  }

  // ============ 左侧装饰区 ============
  &__aside {
    position: relative;
    background: $color-bg-primary;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: $spacing-5xl $spacing-4xl;

    @media (max-width: $breakpoint-tablet) {
      display: none;
    }

    &::before {
      content: "";
      position: absolute;
      inset: 0;
      background: $gradient-hero;
    }
  }

  &__aside-content {
    position: relative;
    z-index: $z-base;
    max-width: 440px;
    width: 100%;
  }

  &__aside-logo {
    display: inline-flex;
    align-items: center;
    gap: $spacing-sm;
    color: $color-text-primary;
    font-weight: $font-weight-semibold;
    font-size: $font-size-h3;
    margin-bottom: $spacing-3xl;

    &:hover { color: $color-primary; }
  }

  &__aside-logo-mark {
    display: inline-flex;
    color: $color-primary;
  }

  &__aside-title {
    font-size: $font-size-display;
    font-weight: $font-weight-semibold;
    color: $color-text-primary;
    line-height: $line-height-tight;
    letter-spacing: $letter-spacing-tight;
    margin-bottom: $spacing-base;
  }

  &__aside-desc {
    font-size: $font-size-body-lg;
    color: $color-text-secondary;
    line-height: $line-height-body;
    margin-bottom: $spacing-3xl;
  }

  &__aside-features {
    list-style: none;
    padding: 0;
    margin: 0 0 $spacing-3xl;
    display: flex;
    flex-direction: column;
    gap: $spacing-md;
  }

  &__aside-features li {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
    color: $color-text-primary;
    font-size: $font-size-body;
  }

  &__aside-feature-dot {
    display: inline-block;
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: $color-primary;
    flex-shrink: 0;
  }

  &__aside-foot {
    color: $color-text-placeholder;
    font-size: $font-size-small;
    padding-top: $spacing-xl;
    border-top: 1px solid $color-divider;
  }

  &__aside-pattern {
    position: absolute;
    bottom: -80px;
    right: -80px;
    width: 320px;
    height: 320px;
    background: radial-gradient(circle, rgba(30, 91, 184, 0.06) 0%, transparent 70%);
    pointer-events: none;
  }

  // ============ 右侧主区 ============
  &__main {
    display: flex;
    align-items: center;
    justify-content: center;
    padding: $spacing-3xl $spacing-xl;
    background: $color-bg-secondary;
  }

  &__main-inner {
    width: 100%;
    max-width: 400px;
  }

  &__mobile-logo {
    display: none;
    align-items: center;
    justify-content: center;
    gap: $spacing-sm;
    margin-bottom: $spacing-2xl;
    color: $color-text-primary;
    font-weight: $font-weight-semibold;
    font-size: $font-size-h3;

    @media (max-width: $breakpoint-tablet) {
      display: inline-flex;
    }
  }

  &__mobile-logo-mark {
    display: inline-flex;
    color: $color-primary;
  }

  &__card-wrap {
    background: $color-bg-primary;
    border: 1px solid $color-border;
    border-radius: $radius-xl;
    padding: $spacing-2xl;
    box-shadow: $shadow-card-elevated;

    @media (max-width: $breakpoint-mobile) {
      padding: $spacing-xl;
    }
  }

  // ============ Tabs ============
  &__tabs {
    position: relative;
    display: grid;
    grid-template-columns: 1fr 1fr;
    background: $color-bg-secondary;
    border-radius: $radius-base;
    padding: 4px;
    margin-bottom: $spacing-2xl;
  }

  &__tab {
    position: relative;
    z-index: $z-base;
    padding: $spacing-sm 0;
    color: $color-text-secondary;
    font-size: $font-size-body;
    font-weight: $font-weight-medium;
    border-radius: $radius-sm;
    transition: color $transition-fast;

    &.is-active {
      color: $color-primary;
    }
  }

  &__tabs-indicator {
    position: absolute;
    top: 4px;
    left: 4px;
    width: calc(50% - 4px);
    height: calc(100% - 8px);
    background: $color-bg-primary;
    border-radius: $radius-sm;
    box-shadow: $shadow-card-hover;
    transition: transform $transition-base $ease-spring;

    &.is-right {
      transform: translateX(100%);
    }
  }

  // ============ 标题 ============
  &__head {
    margin-bottom: $spacing-xl;
  }

  &__title {
    font-size: $font-size-h1;
    font-weight: $font-weight-semibold;
    color: $color-text-primary;
    letter-spacing: $letter-spacing-tight;
    margin-bottom: $spacing-xs;
  }

  &__subtitle {
    color: $color-text-secondary;
    font-size: $font-size-small;
  }

  // ============ 错误提示 ============
  &__error {
    display: flex;
    align-items: center;
    gap: $spacing-sm;
    padding: $spacing-sm $spacing-md;
    margin-bottom: $spacing-base;
    background: $color-danger-soft;
    border-left: 3px solid $color-danger;
    border-radius: $radius-base;
    color: $color-danger;
    font-size: $font-size-small;
  }

  &__error-icon {
    display: inline-flex;
    flex-shrink: 0;
  }

  // ============ 表单 ============
  &__form {
    display: flex;
    flex-direction: column;
    gap: $spacing-base;
  }

  &__field {
    display: flex;
    flex-direction: column;
    gap: $spacing-xs;
  }

  &__label {
    font-size: $font-size-small;
    font-weight: $font-weight-medium;
    color: $color-text-primary;
    letter-spacing: $letter-spacing-wide;
  }

  &__input-wrap {
    display: flex;
    align-items: center;
    height: $height-input;
    padding: 0 $spacing-md;
    background: $color-bg-primary;
    border: 1px solid $color-border;
    border-radius: $radius-base;
    transition: border-color $transition-fast, box-shadow $transition-fast;

    &:focus-within {
      border-color: $color-primary;
      box-shadow: $shadow-focus;
    }

    &.is-error {
      border-color: $color-danger;

      &:focus-within {
        box-shadow: $shadow-focus-danger;
      }
    }
  }

  &__input-icon {
    display: inline-flex;
    color: $color-text-placeholder;
    margin-right: $spacing-sm;
    flex-shrink: 0;
  }

  &__input {
    flex: 1;
    height: 100%;
    border: none;
    outline: none;
    background: transparent;
    font-size: $font-size-body;
    color: $color-text-primary;

    &::placeholder { color: $color-text-placeholder; }
  }

  &__input-action {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    width: 28px;
    height: 28px;
    color: $color-text-secondary;
    border-radius: $radius-sm;
    flex-shrink: 0;
    transition: color $transition-fast, background $transition-fast;

    &:hover {
      color: $color-primary;
      background: $color-primary-soft;
    }
  }

  &__field-error {
    color: $color-danger;
    font-size: $font-size-caption;
    padding-left: $spacing-xs;
  }

  // ============ 验证码 ============
  &__captcha-row {
    display: grid;
    grid-template-columns: 1fr 120px;
    gap: $spacing-sm;
  }

  &__captcha {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: $spacing-sm;
    height: $height-input;
    padding: 0 $spacing-md;
    background: $color-primary-soft;
    border: 1px solid $color-primary;
    border-radius: $radius-base;
    color: $color-primary;
    font-family: $font-family-mono;
    transition: all $transition-fast;

    &:hover {
      background: $color-primary;
      color: $color-text-inverse;
    }
  }

  &__captcha-text {
    font-size: $font-size-body-lg;
    font-weight: $font-weight-semibold;
    letter-spacing: $letter-spacing-wider;
    font-family: $font-family-mono;
  }

  &__captcha-refresh {
    display: inline-flex;
    color: currentColor;
    opacity: 0.7;

    &:hover { opacity: 1; }
  }

  // ============ 协议同意 ============
  &__agreement {
    display: flex;
    align-items: flex-start;
    gap: $spacing-sm;
    color: $color-text-secondary;
    font-size: $font-size-small;
    line-height: $line-height-body;
    cursor: pointer;
  }

  &__agreement-text {
    flex: 1;
  }

  &__agreement-link {
    color: $color-primary;
    text-decoration: none;

    &:hover { text-decoration: underline; }
  }

  &__checkbox {
    width: 14px;
    height: 14px;
    margin-top: 2px;
    accent-color: $color-primary;
    cursor: pointer;
    flex-shrink: 0;
  }

  // ============ 表单额外项 ============
  &__form-extra {
    display: flex;
    align-items: center;
    justify-content: space-between;
    font-size: $font-size-small;
  }

  &__remember {
    display: inline-flex;
    align-items: center;
    gap: $spacing-xs;
    color: $color-text-secondary;
    cursor: pointer;
  }

  &__forgot {
    color: $color-primary;
    transition: color $transition-fast;

    &:hover { color: $color-primary-hover; }
  }

  // ============ 分割线 ============
  &__divider {
    position: relative;
    text-align: center;
    margin: $spacing-xl 0 $spacing-base;

    &::before {
      content: "";
      position: absolute;
      top: 50%;
      left: 0;
      right: 0;
      height: 1px;
      background: $gradient-divider;
    }
  }

  &__divider-text {
    position: relative;
    z-index: $z-base;
    padding: 0 $spacing-md;
    background: $color-bg-primary;
    color: $color-text-placeholder;
    font-size: $font-size-small;
  }

  // ============ 第三方登录 ============
  &__oauth {
    display: flex;
    justify-content: center;
  }

  &__oauth-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: $spacing-sm;
    width: 100%;
    height: $height-input;
    background: $color-bg-primary;
    border: 1px solid $color-border;
    border-radius: $radius-base;
    color: $color-text-primary;
    font-size: $font-size-body;
    font-weight: $font-weight-medium;
    transition: all $transition-fast;

    &:hover {
      border-color: $color-text-primary;
      background: $color-bg-secondary;
    }
  }

  // ============ 底部 ============
  &__foot {
    text-align: center;
    margin-top: $spacing-xl;
    color: $color-text-secondary;
    font-size: $font-size-small;
  }

  &__foot-link {
    color: $color-primary;
    font-weight: $font-weight-medium;
    transition: color $transition-fast;

    &:hover { color: $color-primary-hover; }
  }

  &__legal {
    margin-top: $spacing-xl;
    text-align: center;
    color: $color-text-placeholder;
    font-size: $font-size-caption;
    line-height: $line-height-body;
    max-width: 360px;
    margin-left: auto;
    margin-right: auto;
  }
}
</style>
