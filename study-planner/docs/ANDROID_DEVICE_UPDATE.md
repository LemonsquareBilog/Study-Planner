# Android device update — 2026-10-18

The updated Study Planner APK was built and installed successfully on the connected Android phone (model 25118PC98G).

- Used the existing Microsoft JDK 21 at `C:/Program Files/Microsoft/jdk-21.0.12.101-hotspot`. This resolves the earlier build blocker; no JDK installation or reduced Java requirements were needed.
- Full Gradle `assembleDebug --offline` succeeded, including the native Keystore plugin and updated manifest. Build log: `.verification/android-device-update-build.txt`.
- Compared old/new APK signing certificates: SHA-256 matched. Saved the previously installed APK at `.verification/rollback-auth/phone-before-update.apk`.
- Installed `android/app/build/outputs/apk/debug/app-debug.apk` with `adb install -r -t`; Android returned `Success`. No uninstall or app-data clear was performed.
- Launched `com.studyplanner.app/.MainActivity`. Verified its process was running and its activity was foreground. Android reported `lastUpdateTime=2026-10-18 07:11:17`; the original installation date remained unchanged.
- This is an in-place debug build, version 1.0 / code 1, signed with the existing debug certificate. It is not a store release.
- The bundled API endpoint is `https://study-planner-gamma-seven.vercel.app/api`; no laptop development server is configured.

Successful installation and startup do not establish successful login. No production credentials were entered, no physical-device login/cellular test was performed, and backend/web source changes have not been deployed by this update. Use production accounts with the production backend; development accounts belong to the separate development database.

This record supersedes the APK-build and device-installation blockers in the earlier implementation/verification reports. Production deployment and end-to-end authentication checks remain outstanding.

Later verification: after restoring the hosted demo accounts, both the administrator and Alex passed login and dashboard loading on this physical phone. See [the hosted demo account record](HOSTED_DEMO_ACCOUNTS.md). The laptop's separate localhost repair is documented in [LOCAL_LOGIN_FIX.md](LOCAL_LOGIN_FIX.md).
