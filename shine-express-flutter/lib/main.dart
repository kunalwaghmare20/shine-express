import 'package:flutter/material.dart';
import 'package:hive_flutter/hive_flutter.dart';
import 'package:provider/provider.dart';
import 'config/app_config.dart';
import 'providers/auth_provider.dart';
import 'providers/staff_tab_refresh.dart';
import 'providers/theme_controller.dart';
import 'router/app_router.dart';
import 'services/api_client.dart';
import 'services/offline_job_sync_service.dart';
import 'services/staff_location_tracker.dart';
import 'theme/app_theme.dart';

Future<void> main() async {
  WidgetsFlutterBinding.ensureInitialized();
  await Hive.initFlutter();
  runApp(const ShineExpressApp());
}

class ShineExpressApp extends StatefulWidget {
  const ShineExpressApp({super.key});

  @override
  State<ShineExpressApp> createState() => _ShineExpressAppState();
}

class _ShineExpressAppState extends State<ShineExpressApp> {
  late final ApiClient _api = ApiClient();
  late final OfflineJobSyncService _offlineSync = OfflineJobSyncService(_api);
  late final StaffLocationTracker _locationTracker = StaffLocationTracker(_api);
  late final AuthProvider _auth = AuthProvider(_api);
  late final StaffTabRefresh _staffTabs = StaffTabRefresh();
  late final ThemeController _theme = ThemeController();
  late final _router = createRouter(_auth);

  @override
  void initState() {
    super.initState();
    _auth.bootstrap();
    _offlineSync.init();
  }

  @override
  Widget build(BuildContext context) {
    return MultiProvider(
      providers: [
        Provider.value(value: _api),
        ChangeNotifierProvider.value(value: _offlineSync),
        Provider.value(value: _locationTracker),
        ChangeNotifierProvider.value(value: _auth),
        ChangeNotifierProvider.value(value: _staffTabs),
        ChangeNotifierProvider.value(value: _theme),
      ],
      child: ListenableBuilder(
        listenable: Listenable.merge([_auth, _theme]),
        builder: (context, _) {
          return MaterialApp.router(
            title: 'Shine Express',
            debugShowCheckedModeBanner: false,
            theme: AppTheme.light,
            darkTheme: AppTheme.dark,
            themeMode: _theme.mode,
            routerConfig: _router,
            builder: (context, child) {
              if (_auth.loading) {
                return const Material(
                  child: Center(child: CircularProgressIndicator()),
                );
              }
              return child ?? const SizedBox.shrink();
            },
          );
        },
      ),
    );
  }
}

String get apiBaseUrl => AppConfig.apiBaseUrl;
