import 'package:flutter/foundation.dart';

/// Notifies staff screens when a bottom-nav tab is selected so lists can reload.
class StaffTabRefresh extends ChangeNotifier {
  int index = 0;

  void select(int i) {
    index = i;
    notifyListeners();
  }
}
