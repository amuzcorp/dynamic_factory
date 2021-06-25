# Dynamic Factory Extension

사용자 정의문서(CPT)를 이용하여 다이나믹한 기능들을 구현 할 수 있습니다.
CPT 와 확장변수, 카테고리를 연동하여 사용합니다.

#### 1. 사용자 정의 문서 (Custom Post Page)
- 사용자 정의 문서 유형을 생성 할 수 있습니다. (생성된 CPT 는 Document 모델을 상속 받은 CptDocument 모델을 사용)
- 자동 생성된 관리 페이지에서 사용자 문서 CRUD
- 사용자 정의 문서 모듈을 생성하여 메뉴에 등록, 스킨 변경하여 사용 가능 (List, Create, Edit, Show 페이지)
- 휴지통, 댓글 기능
- 에디터 선택, 출력 필드 선택, List Order 설정
- 확장변수(Dynamic Field) 사용 가능

#### 2. 카테고리 (Taxonomy)
- 카테고리를 생성/편집 기능 (생성된 카테고리는 Category, CategoryItem 모델을 사용)
- 생성된 카테고리를 사용할 CPT 유형을 선택 할 수 있습니다 (해당 CPT 관리페이지에 자동으로 카테고리 선택 기능이 들어감)
- Category 단위로 확장변수 선언하여 사용 가능, CategoryItem 단위로 확장변수 데이터 저장
- 텍소노미 아카이브 모듈을 생성하여 메뉴에 등록, 스킨 변경하여 사용 가능 (List, Create, Edit, Show 페이지)
   
#### 3. Third Party Plugin 지원
- 외부 플러그인에서 정해진 형식과 이름으로 XeRegister 에 등록시 자동으로 CPT 유형이 생성됨
- 해당 CPT의 카테고리, 확장변수 등을 미리 정의하여 생성   
<br>

---------------

<br>

# 개발자 문서

## 1. Model
### 1.1. CptDocument

기본적으로 Document 모델을 상속받아서 사용한다.  
scope를 이용하여 기준으로 발행, 예약, 임시, 비공개를 구분한다.

##### hasDocument($field_id) 메소드
>현재 문서가 가지고 있는 관련 문서들을 불러 올 수 있다.  
>불러온 문서에는 확장필드가 붙어서 나온다.  
>첫번째 인자에는 해당 다이나믹 필드의 id가 들어감
```php
// 사용예시
$docs = $item->hasDocument('field_id');
foreach($docs as $doc) {
  ...
}
```

##### belongDocument($field_id, $source_group) 메소드
>현재 문서를 가지고 있는 타 문서를 불러 올 수 있다.  
>불러온 문서에는 확장필드가 붙어서 나온다.  
>첫번째 인자에는 해당 다이나믹 필드의 id가 들어감  
>두번째 인자에는 현재 문서의 group 이 들어감  
```php
// 사용예시
$docs = $item->belongDocument('field_id', 'documents_program');
foreach($docs as $doc) {
  ...
}
```

##### schedule() 메소드
>현재 문서와 연결된 일정 리스트를 가져온다.  
>예약 플러그인의 BookedSchedule 모델과 연결되어 있으며 해당 플러그인이 설치되어있어야 동작함  
>bk_schedule 테이블과 Join
```php
// 사용예시
$scedules = $item->schedule();
```

