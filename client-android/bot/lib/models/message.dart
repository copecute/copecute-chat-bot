class Message {
  final String text;
  final bool isSentByUser;
  final DateTime timestamp;

  Message({
    required this.text,
    required this.isSentByUser,
    required this.timestamp,
  });
}
