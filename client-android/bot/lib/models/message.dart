class Message {
  final String text;
  final bool isSentByUser;
  final DateTime timestamp;
  final bool isDefaultResponse;

  Message({
    required this.text,
    required this.isSentByUser,
    required this.timestamp,
    this.isDefaultResponse = false,
  });
}
