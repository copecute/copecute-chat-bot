class User {
  final int userId;
  final String username;
  final String email;
  final int level;
  final String fullName;
  final String? avatar;
  final String token;
  final int quota;
  final String? bio;
  final String? phone;
  final String gender;
  final String? birthday;
  final String? address;
  final String? createdAt;

  User({
    required this.userId,
    required this.username,
    required this.email,
    required this.level,
    required this.fullName,
    this.avatar,
    required this.token,
    required this.quota,
    this.bio,
    this.phone,
    this.gender = 'other',
    this.birthday,
    this.address,
    this.createdAt,
  });

  factory User.fromJson(Map<String, dynamic> json) {
    return User(
      userId: int.parse(json['user_id'].toString()),
      username: json['username'].toString(),
      email: json['email'].toString(),
      level: int.parse(json['level'].toString()),
      fullName: json['full_name']?.toString() ?? '',
      avatar: json['avatar']?.toString(),
      token: json['token'].toString(),
      quota: int.parse(json['quota'].toString()),
      bio: json['bio']?.toString(),
      phone: json['phone']?.toString(),
      gender: json['gender']?.toString() ?? 'other',
      birthday: json['birthday']?.toString(),
      address: json['address']?.toString(),
      createdAt: json['created_at']?.toString(),
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'user_id': userId,
      'username': username,
      'email': email,
      'level': level,
      'full_name': fullName,
      'avatar': avatar,
      'token': token,
      'quota': quota,
      'bio': bio,
      'phone': phone,
      'gender': gender,
      'birthday': birthday,
      'address': address,
      'created_at': createdAt,
    };
  }
}
